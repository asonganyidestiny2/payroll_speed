document.addEventListener('DOMContentLoaded', () => {

    // Function to handle the scroll-based animations
    const handleAnimations = () => {
        const elements = document.querySelectorAll('.feature-card, .about-section, .contact-section');

        const observerOptions = {
            root: null, // viewport
            rootMargin: '0px',
            threshold: 0.2 // Trigger when 20% of the element is visible
        };

        const observerCallback = (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target); // Stop observing after it becomes visible
                }
            });
        };

        const observer = new IntersectionObserver(observerCallback, observerOptions);

        elements.forEach(el => {
            el.classList.add('fade-in-on-scroll'); // Add the base animation class
            observer.observe(el); // Start observing
        });
    };

    // Run the animation handler on page load
    handleAnimations();

});