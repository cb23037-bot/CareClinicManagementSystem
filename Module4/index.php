<?php
session_start();
require_once 'config/database.php';
// If already logged in, redirect to appropriate dashboard
if(isset($_SESSION['user_id'])) {
    switch($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'doctor':
            header('Location: doctor/dashboard.php');
            break;
        case 'patient':
            header('Location: patient/dashboard.php');
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareClinic - Your Health, Our Priority</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }
    </style>
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <img src="logo.png" alt="Logo" class="h-10 w-10">
                    <span class="ml-2 text-xl font-bold text-indigo-600">CareClinic</span>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="#home" class="text-gray-700 hover:text-indigo-600 transition">Home</a>
                    <a href="#features" class="text-gray-700 hover:text-indigo-600 transition">Features</a>
                    <a href="#about" class="text-gray-700 hover:text-indigo-600 transition">About</a>
                    <a href="#contact" class="text-gray-700 hover:text-indigo-600 transition">Contact</a>
                </div>
                <div class="flex space-x-3">
                    <a href="login.php" class="px-4 py-2 text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-50 transition">Login</a>
                    <a href="register.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-gradient pt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold text-white leading-tight">
                        Your Health, <br>
                        <span class="text-yellow-300">Our Priority</span>
                    </h1>
                    <p class="text-white text-lg mt-4 opacity-90">
                        Experience modern healthcare management with CareClinic. Book appointments, access medical records, and connect with top doctors online.
                    </p>
                    <div class="mt-8 flex space-x-4">
                        <a href="register.php" class="bg-white text-indigo-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition shadow-lg">
                            Get Started
                        </a>
                        <a href="#features" class="border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-indigo-600 transition">
                            Learn More
                        </a>
                    </div>
                </div>
                <div class="hidden md:block">
                    <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?ixlib=rb-4.0.3&w=500&q=80" alt="Healthcare" class="rounded-2xl shadow-2xl">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Why Choose CareClinic?</h2>
                <p class="text-gray-600 mt-4">Comprehensive healthcare management solutions</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl p-6 text-center card-hover">
                    <div class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Easy Appointments</h3>
                    <p class="text-gray-600">Book appointments with top doctors instantly, anytime anywhere.</p>
                </div>
                <div class="bg-white rounded-xl p-6 text-center card-hover">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Digital Records</h3>
                    <p class="text-gray-600">Access your medical history and records securely online.</p>
                </div>
                <div class="bg-white rounded-xl p-6 text-center card-hover">
                    <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Expert Doctors</h3>
                    <p class="text-gray-600">Connect with experienced and certified medical professionals.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="hero-gradient py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 text-center text-white">
                <div>
                    <div class="text-4xl font-bold">10K+</div>
                    <div class="text-sm opacity-90 mt-2">Happy Patients</div>
                </div>
                <div>
                    <div class="text-4xl font-bold">50+</div>
                    <div class="text-sm opacity-90 mt-2">Expert Doctors</div>
                </div>
                <div>
                    <div class="text-4xl font-bold">100%</div>
                    <div class="text-sm opacity-90 mt-2">Secure Records</div>
                </div>
                <div>
                    <div class="text-4xl font-bold">24/7</div>
                    <div class="text-sm opacity-90 mt-2">Online Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center">
                        <img src="logo.png" alt="Logo" class="h-10 w-10">
                        <span class="ml-2 text-xl font-bold">CareClinic</span>
                    </div>
                    <p class="mt-4 text-gray-400">Providing quality healthcare services since 2024.</p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#home" class="hover:text-white transition">Home</a></li>
                        <li><a href="#features" class="hover:text-white transition">Features</a></li>
                        <li><a href="#about" class="hover:text-white transition">About Us</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>📞 +1 (555) 123-4567</li>
                        <li>✉️ info@careclinic.com</li>
                        <li>📍 123 Healthcare Ave, Medical City</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Working Hours</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>Mon-Fri: 8:00 AM - 8:00 PM</li>
                        <li>Saturday: 9:00 AM - 5:00 PM</li>
                        <li>Sunday: Closed</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 CareClinic. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>