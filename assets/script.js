document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.toggle('hidden');
            menuToggle.setAttribute('aria-expanded', String(!isHidden));
        });

        document.addEventListener('click', (event) => {
            const insideHeader = event.target.closest('header');

            if (!insideHeader && window.innerWidth < 768) {
                mobileMenu.classList.add('hidden');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    window.setTimeout(() => {
        document.querySelectorAll('.flash-message').forEach((message) => {
            message.classList.add('opacity-0');
        });
    }, 5000);
});
