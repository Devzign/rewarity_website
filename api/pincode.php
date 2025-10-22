<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// GET is open (no auth required)
if ($method === 'GET') {
  $pincode = isset($_GET['pincode']) ? trim((string)$_GET['pincode']) : '';
  if ($pincode === '') {
    json_response(422, ['error' => 'pincode is required']);
  }

  try {
    $sql = 'SELECT pm.Id AS PincodeId, pm.Pincode,
                   c.Id AS CityId, c.CityName,
                   s.Id AS StateId, s.StateName,
                   co.Id AS CountryId, co.CountryName
            FROM pincode_master pm
            INNER JOIN city_master c ON c.Id = pm.CityId
            LEFT JOIN state_master s ON s.Id = c.StateId
            LEFT JOIN country_master co ON co.Id = s.CountryId
            WHERE pm.Pincode = ?
            LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $pincode);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row) {
      json_response(200, [
        'pincode' => $row['Pincode'],
        'city' => [
          'id' => (int)$row['CityId'],
          'name' => $row['CityName'],
        ],
        'state' => [
          'id' => $row['StateId'] !== null ? (int)$row['StateId'] : null,
          'name' => $row['StateName'] ?? null,
        ],
        'country' => [
          'id' => $row['CountryId'] !== null ? (int)$row['CountryId'] : null,
          'name' => $row['CountryName'] ?? null,
        ],
      ]);
    }

    // Fallback: minimal built-in mapping for common Indian pincodes
    $fallback = [
      // Delhi region examples (adjust as needed)
      '110001' => ['city' => 'New Delhi', 'state' => 'Delhi', 'country' => 'India'],
      '110044' => ['city' => 'South Delhi', 'state' => 'Delhi', 'country' => 'India'],
    ];
    if (isset($fallback[$pincode])) {
      $f = $fallback[$pincode];
      json_response(200, [
        'pincode' => $pincode,
        'city' => ['id' => null, 'name' => $f['city']],
        'state' => ['id' => null, 'name' => $f['state']],
        'country' => ['id' => null, 'name' => $f['country']],
        'note' => 'Returned from fallback dictionary; no DB mapping yet.'
      ]);
    }

    // External lookup via India Post public API
    $autolink = isset($_GET['autolink']) ? (int)(strtolower((string)$_GET['autolink']) === 'true' || $_GET['autolink'] == '1') : 0;
    $extUrl = 'https://api.postalpincode.in/pincode/' . rawurlencode($pincode);
    $ch = curl_init($extUrl);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 4,
      CURLOPT_CONNECTTIMEOUT => 3,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $extResp = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($extResp !== false && $httpCode >= 200 && $httpCode < 300) {
      $extData = json_decode($extResp, true);
      if (is_array($extData) && isset($extData[0]['Status']) && $extData[0]['Status'] === 'Success') {
        $offices = $extData[0]['PostOffice'] ?? [];
        if (is_array($offices) && !empty($offices)) {
          $office = $offices[0];
          $cityName = (string)($office['District'] ?? '');
          $stateName = (string)($office['State'] ?? '');
          $countryName = (string)($office['Country'] ?? 'India');

          // Try to find IDs in our DB
          $stateRow = null; $cityRow = null; $countryRow = null;
          if ($countryName !== '') {
            $cs = $conn->prepare('SELECT Id, CountryName FROM country_master WHERE LOWER(CountryName) = LOWER(?) LIMIT 1');
            $cs->bind_param('s', $countryName);
            $cs->execute();
            $cres = $cs->get_result();
            $countryRow = $cres->fetch_assoc();
            $cs->close();
          }
          if ($stateName !== '') {
            if ($countryRow) {
              $sid = $conn->prepare('SELECT Id, StateName, CountryId FROM state_master WHERE LOWER(StateName) = LOWER(?) AND CountryId = ? LIMIT 1');
              $cid = (int)$countryRow['Id'];
              $sid->bind_param('si', $stateName, $cid);
            } else {
              $sid = $conn->prepare('SELECT Id, StateName, CountryId FROM state_master WHERE LOWER(StateName) = LOWER(?) LIMIT 1');
              $sid->bind_param('s', $stateName);
            }
            $sid->execute();
            $sres = $sid->get_result();
            $stateRow = $sres->fetch_assoc();
            $sid->close();
          }
          if ($cityName !== '') {
            if ($stateRow) {
              $cidq = $conn->prepare('SELECT Id, CityName, StateId FROM city_master WHERE LOWER(CityName) = LOWER(?) AND StateId = ? LIMIT 1');
              $sidv = (int)$stateRow['Id'];
              $cidq->bind_param('si', $cityName, $sidv);
            } else {
              $cidq = $conn->prepare('SELECT Id, CityName, StateId FROM city_master WHERE LOWER(CityName) = LOWER(?) LIMIT 1');
              $cidq->bind_param('s', $cityName);
            }
            $cidq->execute();
            $cires = $cidq->get_result();
            $cityRow = $cires->fetch_assoc();
            $cidq->close();
          }

          // Optionally autolink pincode->city when we have a city id
          if ($autolink && $cityRow) {
            $check = $conn->prepare('SELECT Id FROM pincode_master WHERE Pincode = ? LIMIT 1');
            $check->bind_param('s', $pincode);
            $check->execute();
            $rr = $check->get_result();
            $exists = $rr->fetch_assoc();
            $check->close();
            if (!$exists) {
              $pid = next_numeric_id($conn, 'pincode_master');
              $ins = $conn->prepare('INSERT INTO pincode_master (Id, Pincode, CityId) VALUES (?, ?, ?)');
              $cityId = (int)$cityRow['Id'];
              $ins->bind_param('isi', $pid, $pincode, $cityId);
              $ins->execute();
              $ins->close();
            }
          }

          json_response(200, [
            'pincode' => $pincode,
            'city' => ['id' => $cityRow ? (int)$cityRow['Id'] : null, 'name' => $cityName],
            'state' => ['id' => $stateRow ? (int)$stateRow['Id'] : null, 'name' => $stateName],
            'country' => ['id' => $countryRow ? (int)$countryRow['Id'] : null, 'name' => $countryName],
            'note' => $cityRow ? 'Matched via external lookup and DB.' : 'Returned from external lookup; no DB mapping.'
          ]);
        }
      }
    }

    // If we got here, no DB match and external lookup failed
    json_response(404, ['error' => 'Pincode not found']);
  } catch (mysqli_sql_exception $exception) {
    json_response(500, ['error' => 'Failed to lookup pincode', 'details' => $exception->getMessage()]);
  }
}

// POST modifies data; require auth
if ($method === 'POST') {
  require_auth();
  // Upsert a pincode mapping. If city_id is not provided, resolve via India Post API and match DB.
  $payload = get_json_body();
  if (empty($payload['pincode'])) {
    json_response(422, ['error' => 'pincode is required']);
  }
  $pincode = trim((string)$payload['pincode']);
  $cityId = isset($payload['city_id']) ? (int)$payload['city_id'] : null;

  try {
    // If city_id not provided, resolve using India Post API then match DB
    $crow = null; $stateRow = null; $countryRow = null;
    if ($cityId === null) {
      $extUrl = 'https://api.postalpincode.in/pincode/' . rawurlencode($pincode);
      $ch = curl_init($extUrl);
      curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>4, CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_HTTPHEADER=>['Accept: application/json']]);
      $extResp = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      if ($extResp === false || $httpCode < 200 || $httpCode >= 300) {
        json_response(422, ['error' => 'Unable to resolve pincode via external service']);
      }
      $extData = json_decode($extResp, true);
      if (!is_array($extData) || ($extData[0]['Status'] ?? '') !== 'Success') {
        json_response(422, ['error' => 'Pincode not found in external service']);
      }
      $offices = $extData[0]['PostOffice'] ?? [];
      if (empty($offices)) {
        json_response(422, ['error' => 'Pincode not found in external service']);
      }
      $office = $offices[0];
      $cityName = (string)($office['District'] ?? '');
      $stateName = (string)($office['State'] ?? '');
      $countryName = (string)($office['Country'] ?? 'India');

      // Match to DB
      if ($countryName !== '') {
        $cs = $conn->prepare('SELECT Id, CountryName FROM country_master WHERE LOWER(CountryName)=LOWER(?) LIMIT 1');
        $cs->bind_param('s', $countryName);
        $cs->execute();
        $cres = $cs->get_result();
        $countryRow = $cres->fetch_assoc();
        $cs->close();
      }
      if ($stateName !== '') {
        if ($countryRow) {
          $sid = $conn->prepare('SELECT Id, StateName FROM state_master WHERE LOWER(StateName)=LOWER(?) AND CountryId = ? LIMIT 1');
          $cid = (int)$countryRow['Id'];
          $sid->bind_param('si', $stateName, $cid);
        } else {
          $sid = $conn->prepare('SELECT Id, StateName FROM state_master WHERE LOWER(StateName)=LOWER(?) LIMIT 1');
          $sid->bind_param('s', $stateName);
        }
        $sid->execute();
        $sres = $sid->get_result();
        $stateRow = $sres->fetch_assoc();
        $sid->close();
      }
      if ($cityName !== '') {
        if ($stateRow) {
          $cidq = $conn->prepare('SELECT Id, CityName FROM city_master WHERE LOWER(CityName)=LOWER(?) AND StateId = ? LIMIT 1');
          $sidv = (int)$stateRow['Id'];
          $cidq->bind_param('si', $cityName, $sidv);
        } else {
          $cidq = $conn->prepare('SELECT Id, CityName FROM city_master WHERE LOWER(CityName)=LOWER(?) LIMIT 1');
          $cidq->bind_param('s', $cityName);
        }
        $cidq->execute();
        $cires = $cidq->get_result();
        $crow = $cires->fetch_assoc();
        $cidq->close();
      }
      if (!$crow) {
        json_response(422, ['error' => 'City not present in DB for this pincode', 'suggested_city' => $cityName, 'suggested_state' => $stateName, 'suggested_country' => $countryName]);
      }
      $cityId = (int)$crow['Id'];
    } else {
      // Validate provided city exists
      $c = $conn->prepare('SELECT c.Id, c.CityName, s.Id AS StateId, s.StateName, co.Id AS CountryId, co.CountryName FROM city_master c LEFT JOIN state_master s ON s.Id = c.StateId LEFT JOIN country_master co ON co.Id = s.CountryId WHERE c.Id = ? LIMIT 1');
      $c->bind_param('i', $cityId);
      $c->execute();
      $cres = $c->get_result();
      $crow = $cres->fetch_assoc();
      $c->close();
      if (!$crow) {
        json_response(422, ['error' => 'Invalid city_id']);
      }
    }

    // Upsert pincode
    $check = $conn->prepare('SELECT Id FROM pincode_master WHERE Pincode = ? LIMIT 1');
    $check->bind_param('s', $pincode);
    $check->execute();
    $r = $check->get_result();
    $row = $r->fetch_assoc();
    $check->close();

    if ($row) {
      $pid = (int)$row['Id'];
      $upd = $conn->prepare('UPDATE pincode_master SET CityId = ? WHERE Id = ?');
      $upd->bind_param('ii', $cityId, $pid);
      $upd->execute();
      $upd->close();
      $status = 200;
      $message = 'Pincode mapping updated';
    } else {
      $pid = next_numeric_id($conn, 'pincode_master');
      $ins = $conn->prepare('INSERT INTO pincode_master (Id, Pincode, CityId) VALUES (?, ?, ?)');
      $ins->bind_param('isi', $pid, $pincode, $cityId);
      $ins->execute();
      $ins->close();
      $status = 201;
      $message = 'Pincode mapping created';
    }

    json_response($status, [
      'message' => $message,
      'pincode' => $pincode,
      'city' => ['id' => (int)$crow['Id'], 'name' => $crow['CityName']],
      // state/country fields present if validated with provided city_id; may be null when resolved via city only
      'state' => ['id' => isset($crow['StateId']) ? ((int)$crow['StateId']) : null, 'name' => $crow['StateName'] ?? null],
      'country' => ['id' => isset($crow['CountryId']) ? ((int)$crow['CountryId']) : null, 'name' => $crow['CountryName'] ?? null]
    ]);
  } catch (mysqli_sql_exception $exception) {
    json_response(500, ['error' => 'Failed to upsert pincode', 'details' => $exception->getMessage()]);
  }
}

json_response(405, ['error' => 'Method not allowed']);
