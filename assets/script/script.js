  // Header scroll effect
  window.addEventListener('scroll', function() {
    const header = document.getElementById('header');
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// Lawyer carousel
const lawyerCards = document.querySelectorAll('.lawyer-card');
let currentLawyer = 0;

function showNextLawyer() {
    lawyerCards[currentLawyer].classList.remove('active');
    currentLawyer = (currentLawyer + 1) % lawyerCards.length;
    lawyerCards[currentLawyer].classList.add('active');
}

setInterval(showNextLawyer, 5000);

// Service card animation on scroll
const serviceCards = document.querySelectorAll('.service-card');

function isElementInViewport(el) {
    const rect = el.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

function animateServiceCards() {
    serviceCards.forEach(card => {
        if (isElementInViewport(card)) {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }
    });
}

window.addEventListener('scroll', animateServiceCards);
window.addEventListener('load', animateServiceCards);

// Initialize service cards with initial state
serviceCards.forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
});