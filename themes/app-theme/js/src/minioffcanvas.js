function initOffcanvas() {
    const triggers = Array.from(document.querySelectorAll('[data-bs-toggle="offcanvas"]'));

    triggers.forEach(trigger => {
        const panel = document.querySelector(trigger.getAttribute('data-bs-target'));
        if (!panel) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            toggle(panel, trigger, !panel.classList.contains('show'));
        });
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        document.querySelectorAll('.offcanvas.show').forEach(panel => {
            if (!panel.contains(e.target)) {
                const trigger = document.querySelector(`[data-bs-target="#${panel.id}"]`);
                toggle(panel, trigger, false);
            }
        });
    });
}

function toggle(panel, trigger, open) {
    panel.classList.toggle('show', open);
    document.body.style.overflow = open ? 'hidden' : '';
    if (trigger) trigger.setAttribute('aria-expanded', String(open));
}

initOffcanvas();
