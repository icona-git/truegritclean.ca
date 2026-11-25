function initCollapse() {
    const fnmap = {
        'toggle': 'toggle',
        'show':   'add',
        'hide':   'remove'
    };

    const collapse = (selector, cmd, trigger) => {
        const targets = Array.from(document.querySelectorAll(selector));
        targets.forEach(target => {
            // Set states for panel and trigger
            target.classList[fnmap[cmd]]('show');
            trigger.classList[fnmap[cmd]]('collapsed');
            // Accessibility aria state
            const ariaState = target.classList.contains('show') ? 'true' : 'false';
            trigger.setAttribute('aria-expanded', ariaState);
        });
    };

    const triggers = Array.from(document.querySelectorAll('[data-bs-toggle="collapse"]'));
    triggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
            const selector = trigger.getAttribute('data-bs-target');
            collapse(selector, 'toggle', trigger);
        }, false);
    });
}

initCollapse();
