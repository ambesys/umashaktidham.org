<?php
echo "PHP Server is working!";
echo "<br>PHP Version: " . phpversion();
echo "<br>Current Directory: " . getcwd();
echo "<br>Files in directory: ";
print_r(scandir('.'));
?>