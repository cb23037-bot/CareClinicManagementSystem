<?php
$plain_password = 'michael123';
// Use PASSWORD_DEFAULT for the strongest, current hashing algorithm (Bcrypt)
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
echo $hashed_password;
?>

<!--admin:admin123  $2y$10$nO2KncmWTC6tHVjZ9CK0lOQsIb3LExXbvVVpNwF27wuRS5ohA4tyW-->
<!--drfatin:arisya123  $2y$10$9fYYglGSdeH0F0PhuzB2VOj2yteM2swHtfASaEjG2Vt4UOTFzMapi -->
<!--ameera:ameera123  $2y$10$McfDhMnKTHjPk6MRDCgnUOdLjpPFb6R1ilF73zQM5Nex.p1JNSoRW -->
<!--sarah:sarah123  $2y$10$5hdR5PDkfvfS8L2T2HoPMu2NqchoAMOU/lwkoNtKL9.eVGX91.y1u -->
<!--michael:michael123  $2y$10$uiEz3A.bly4j2aZs53rJce8DBE5bGQR2nbPymJZTCMpUmc/S0hVvG -->

