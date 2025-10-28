// Minimal JS: mobile nav toggle and small helpers
document.addEventListener('DOMContentLoaded', function(){
  var toggle = document.getElementById('navToggle');
  var nav = document.getElementById('mainNav');
  if(toggle && nav){
    toggle.addEventListener('click', function(){
      nav.classList.toggle('open');
      // simple aria
      var expanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', (!expanded).toString());
    });
  }
});
// This file contains the main JavaScript functionality for the website.

document.addEventListener("DOMContentLoaded", function() {
    // Mobile responsive navigation toggle
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');

    navToggle.addEventListener('click', function() {
        navMenu.classList.toggle('active');
    });

    // Smooth scrolling for internal links
    const internalLinks = document.querySelectorAll('a[href^="#"]');
    internalLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            targetElement.scrollIntoView({ behavior: 'smooth' });
        });
    });

    // Donation form validation
    const donationForm = document.querySelector('#donation-form');
    if (donationForm) {
        donationForm.addEventListener('submit', function(e) {
            const amountInput = document.querySelector('#donation-amount');
            if (amountInput.value <= 0) {
                e.preventDefault();
                alert('Please enter a valid donation amount.');
            }
        });
    }
});