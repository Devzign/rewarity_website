<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rewarity - Loyalty &amp; Scheme Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rewarity - Loyalty & Scheme Management Platform for Distributors and Dealers">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      try {
        tailwind.config = {
          theme: {
            extend: { colors: { rewarity: { green: '#17b348' } } }
          }
        };
      } catch (e) {}
    </script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">

    <!-- Navbar -->
    <header class="bg-white shadow-md fixed w-full z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center py-4 px-6">
            <div class="flex items-center space-x-3">
                <img src="/Logo/REWARITY-01.png" alt="Rewarity Logo" class="h-14" onerror="this.style.display='none'">
                <span class="text-2xl font-bold text-rewarity-green">Rewarity</span>
            </div>
            <nav>
                <ul class="flex space-x-6 font-medium">
                    <li><a href="#about" class="hover:text-[#17b348]">About</a></li>
                    <li><a href="#features" class="hover:text-[#17b348]">Features</a></li>
                    <li><a href="#contact" class="hover:text-[#17b348]">Contact</a></li>
                </ul>
            </nav>
            <div class="hidden md:block">
              <a href="/admin/login.php" class="px-4 py-2 rounded-md text-white bg-[#17b348] hover:bg-green-700 transition">Admin Login</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="h-screen flex flex-col justify-center items-center bg-gradient-to-r from-black to-[#17b348] text-white text-center" id="hero">
        <h2 class="text-5xl font-extrabold mb-4">Rewarity</h2>
        <p class="max-w-xl mb-6 text-lg">
            Loyalty &amp; Rewards Platform for Distributors and Dealers.
            Run geo-fenced schemes, track purchases, and reward automatically.
        </p>
        <div class="flex gap-3">
          <a href="#features" class="bg-white text-[#17b348] font-semibold px-6 py-3 rounded-md shadow hover:bg-gray-100 transition">
              Explore Features
          </a>
          <a href="/admin/login.php" class="bg-transparent border border-white text-white font-semibold px-6 py-3 rounded-md hover:bg-white/10 transition">
              Admin Login
          </a>
        </div>
    </section>

    <!-- About -->
    <section id="about" class="max-w-6xl mx-auto py-20 px-6">
        <h3 class="text-3xl font-bold text-center mb-6 text-black">About Rewarity</h3>
        <p class="text-gray-700 text-center max-w-3xl mx-auto">
            Rewarity is a <span class="text-[#17b348] font-semibold">Loyalty &amp; Scheme Management System</span> that helps
            distributors and dealers create targeted promotions, track progress, and automate reward distribution.
            With geo-fencing, modern engagement, and automated tracking, we simplify loyalty programs for businesses.
        </p>
    </section>

    <!-- Features -->
    <section id="features" class="bg-gray-100 py-20 px-6">
        <h3 class="text-3xl font-bold text-center mb-10 text-black">Key Features</h3>
        <div class="grid md:grid-cols-3 gap-10 max-w-6xl mx-auto">
            <div class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition">
                <h4 class="text-xl font-bold mb-3 text-[#17b348]">Scheme Management</h4>
                <p>Create and manage promotional schemes with step-based tasks and tiered rewards.</p>
            </div>
            <div class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition">
                <h4 class="text-xl font-bold mb-3 text-[#17b348]">Geo-Fenced Rewards</h4>
                <p>Offer rewards targeted by city, state, or country to meet regional sales goals.</p>
            </div>
            <div class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition">
                <h4 class="text-xl font-bold mb-3 text-[#17b348]">Automated Tracking</h4>
                <p>Track dealer progress in real time with automatic winner &amp; reward distribution.</p>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="max-w-6xl mx-auto py-20 px-6 text-center">
        <h3 class="text-3xl font-bold mb-6 text-black">Contact Us</h3>
        <p class="mb-4 text-gray-700">Want to know more about Rewarity?</p>
        <a href="mailto:info@rewarity.com" class="bg-[#17b348] text-white px-6 py-3 rounded-md hover:bg-green-700 transition">
            Email Us
        </a>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-gray-300 py-6 text-center">
        <p>© <?php echo date('Y'); ?> <span class="text-[#17b348] font-semibold">Rewarity</span>. All Rights Reserved.</p>
    </footer>

    <!-- JS: Smooth Scroll -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener("click", function(e) {
                const target = document.querySelector(this.getAttribute("href"));
                if (!target) return;
                e.preventDefault();
                target.scrollIntoView({ behavior: "smooth" });
            });
        });
    </script>

</body>
</html>
