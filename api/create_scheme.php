<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

ensure_method('POST');
$payload = get_json_body();

require_keys($payload, ['scheme_name']);

$schemeName = trim((string)$payload['scheme_name']);
if ($schemeName === '') {
  json_response(422, ['error' => 'Scheme name cannot be empty']);
}

$description = isset($payload['description']) ? trim((string)$payload['description']) : null;
$schemeType = isset($payload['scheme_type']) ? trim((string)$payload['scheme_type']) : null;
$isActive = array_key_exists('is_active', $payload) ? (to_bool($payload['is_active']) ? 1 : 0) : null;

$startDate = isset($payload['start_date']) ? normalize_datetime($payload['start_date']) : null;
$endDate = isset($payload['end_date']) ? normalize_datetime($payload['end_date']) : null;

$details = isset($payload['details']) && is_array($payload['details']) ? $payload['details'] : [];
$steps = isset($payload['steps']) && is_array($payload['steps']) ? $payload['steps'] : [];

try {
  $conn->begin_transaction();

  $schemeStmt = $conn->prepare(
    'INSERT INTO scheme_master (SchemName, IsActive, StartDate, EndDate, Description, SchemeType)
     VALUES (?, ?, ?, ?, ?, ?)'
  );

  $schemeStmt->bind_param(
    'sissss',
    $schemeName,
    $isActive,
    $startDate,
    $endDate,
    $description,
    $schemeType
  );
  $schemeStmt->execute();
  $schemeStmt->close();

  $schemeId = (int)$conn->insert_id;

  $detailCount = 0;
  if (!empty($details)) {
  $detailStmt = $conn->prepare(
    'INSERT INTO scheme_detail (SchemeId, BuyerId, LocationId, SchemProductId, Value, RewardId)
     VALUES (?, ?, ?, ?, ?, ?)'
  );

  $detailSchemeId = $schemeId;
  $buyerId = $locationId = $productId = $value = $rewardId = null;

  $detailStmt->bind_param(
    'iiiiii',
    $detailSchemeId,
    $buyerId,
    $locationId,
    $productId,
    $value,
    $rewardId
  );

    foreach ($details as $detail) {
      if (!is_array($detail)) {
        continue;
      }

      $buyerId = isset($detail['buyer_id']) ? (int)$detail['buyer_id'] : null;
      $locationId = isset($detail['location_id']) ? (int)$detail['location_id'] : null;
      $productId = isset($detail['product_id']) ? (int)$detail['product_id'] : null;
      $value = isset($detail['value']) ? (int)$detail['value'] : null;
      $rewardId = isset($detail['reward_id']) ? (int)$detail['reward_id'] : null;

      $detailStmt->execute();
      $detailCount++;
    }

    $detailStmt->close();
  }

  $stepCount = 0;
  if (!empty($steps)) {
  $stepStmt = $conn->prepare(
    'INSERT INTO scheme_steps (SchemId, ProductId, ValueOrQty, Description, RewardId, StepSequenceOrder)
     VALUES (?, ?, ?, ?, ?, ?)'
  );

  $stepSchemeId = $schemeId;
  $stepProductId = $valueOrQty = $stepRewardId = $sequence = null;
  $stepDescription = null;

  $stepStmt->bind_param(
    'iiisii',
    $stepSchemeId,
    $stepProductId,
    $valueOrQty,
    $stepDescription,
    $stepRewardId,
    $sequence
  );

    foreach ($steps as $step) {
      if (!is_array($step)) {
        continue;
      }

      $stepProductId = isset($step['product_id']) ? (int)$step['product_id'] : null;
      $valueOrQty = isset($step['value_or_qty']) ? (int)$step['value_or_qty'] : null;
      $stepDescription = isset($step['description']) ? trim((string)$step['description']) : null;
      $stepRewardId = isset($step['reward_id']) ? (int)$step['reward_id'] : null;
      $sequence = isset($step['sequence']) ? (int)$step['sequence'] : null;

      $stepStmt->execute();
      $stepCount++;
    }

    $stepStmt->close();
  }

  $conn->commit();

  json_response(201, [
    'message' => 'Scheme created successfully',
    'scheme_id' => $schemeId,
    'details_created' => $detailCount,
    'steps_created' => $stepCount
  ]);
} catch (mysqli_sql_exception $exception) {
  $conn->rollback();

  $error = [
    'error' => 'Failed to create scheme',
    'details' => $exception->getMessage()
  ];

  if ($exception->getCode() === 1062) {
    $error['hint'] = 'Duplicate entry detected';
    json_response(409, $error);
  }

  json_response(500, $error);
}

/**
 * Normalize user-provided datetime input to MySQL format.
 */
function normalize_datetime($value): ?string
{
  if ($value === null || $value === '') {
    return null;
  }

  if ($value instanceof \DateTimeInterface) {
    return $value->format('Y-m-d H:i:s');
  }

  $str = trim((string)$value);

  $patterns = ['Y-m-d H:i:s', 'Y-m-d\TH:i:sP', 'Y-m-d', \DateTimeInterface::RFC3339, \DateTimeInterface::ATOM];

  foreach ($patterns as $pattern) {
    $dt = \DateTime::createFromFormat($pattern, $str);
    if ($dt instanceof \DateTime) {
      return $dt->format('Y-m-d H:i:s');
    }
  }

  $timestamp = strtotime($str);
  return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
}
