/* =========================================================
   BAYU SATRIA — PORTFOLIO SCRIPTS
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {

  /* ---------- Footer year ---------- */
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  /* ---------- Navbar scroll shadow ---------- */
  const navbar = document.getElementById('navbar');
  const scrollProgressBar = document.getElementById('scrollProgressBar');

  function onScroll(){
    if (window.scrollY > 12) navbar.classList.add('scrolled');
    else navbar.classList.remove('scrolled');

    const doc = document.documentElement;
    const scrollTop = window.scrollY;
    const height = doc.scrollHeight - doc.clientHeight;
    const percent = height > 0 ? (scrollTop / height) * 100 : 0;
    if (scrollProgressBar) scrollProgressBar.style.width = percent + '%';
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Mobile menu ---------- */
  const hamburger = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobileMenu');

  function closeMenu(){
    hamburger.classList.remove('open');
    mobileMenu.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }
  function toggleMenu(){
    const isOpen = mobileMenu.classList.toggle('open');
    hamburger.classList.toggle('open', isOpen);
    hamburger.setAttribute('aria-expanded', String(isOpen));
    document.body.style.overflow = isOpen ? 'hidden' : '';
  }
  hamburger.addEventListener('click', toggleMenu);
  mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));

  /* ---------- Smooth scroll for in-page links ---------- */
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
      const id = link.getAttribute('href');
      if (id.length > 1) {
        const target = document.querySelector(id);
        if (target) {
          e.preventDefault();
          const offset = 80;
          const top = target.getBoundingClientRect().top + window.scrollY - offset;
          window.scrollTo({ top, behavior: 'smooth' });
        }
      }
    });
  });

  /* ---------- Active nav link on scroll (scroll-spy) ---------- */
  const sections = document.querySelectorAll('main section[id], section[id]');
  const navLinks = document.querySelectorAll('.nav-link, .mobile-link');

  const spyObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = entry.target.getAttribute('id');
        navLinks.forEach(link => {
          link.classList.toggle('active', link.dataset.section === id);
        });
      }
    });
  }, { rootMargin: '-45% 0px -50% 0px', threshold: 0 });

  sections.forEach(section => spyObserver.observe(section));

  /* ---------- Scroll-reveal animations ---------- */
  const revealEls = document.querySelectorAll('.reveal');
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });

  revealEls.forEach(el => revealObserver.observe(el));

  /* ---------- Animated skill bars ---------- */
  const skillBars = document.querySelectorAll('.skill-bar span');
  const skillObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const bar = entry.target;
        const percent = bar.dataset.percent || 0;
        requestAnimationFrame(() => { bar.style.width = percent + '%'; });
        skillObserver.unobserve(bar);
      }
    });
  }, { threshold: 0.4 });

  skillBars.forEach(bar => skillObserver.observe(bar));

  /* ---------- Cursor glow (desktop only) ---------- */
  const cursorGlow = document.querySelector('.cursor-glow');
  if (window.matchMedia('(hover: hover) and (pointer: fine)').matches && cursorGlow) {
    window.addEventListener('mousemove', (e) => {
      cursorGlow.style.left = e.clientX + 'px';
      cursorGlow.style.top = e.clientY + 'px';
      cursorGlow.classList.add('active');
    });
    document.addEventListener('mouseleave', () => cursorGlow.classList.remove('active'));
  }

  /* ---------- Contact form validation ---------- */
  const form = document.getElementById('contactForm');
  const status = document.getElementById('formStatus');

  function setError(fieldName, message){
    const row = form.querySelector(`#${fieldName}`).closest('.form-row');
    const errorEl = form.querySelector(`.form-error[data-for="${fieldName}"]`);
    if (message) {
      row.classList.add('error');
      errorEl.textContent = message;
    } else {
      row.classList.remove('error');
      errorEl.textContent = '';
    }
  }

  function validate(){
    let valid = true;
    const name = form.name.value.trim();
    const email = form.email.value.trim();
    const message = form.message.value.trim();

    if (!name) { setError('name', 'Please enter your name.'); valid = false; }
    else setError('name', '');

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) { setError('email', 'Please enter your email.'); valid = false; }
    else if (!emailPattern.test(email)) { setError('email', 'Please enter a valid email address.'); valid = false; }
    else setError('email', '');

    if (!message) { setError('message', 'Please write a short message.'); valid = false; }
    else if (message.length < 10) { setError('message', 'Message should be at least 10 characters.'); valid = false; }
    else setError('message', '');

    return valid;
  }

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      status.classList.remove('success', 'error');

      if (!validate()) {
        status.textContent = 'Please fix the highlighted fields.';
        status.classList.add('error');
        return;
      }

      // No backend is connected yet, so we open the visitor's email client
      // with the message pre-filled. Replace this block with a fetch() call
      // to your own backend or a form service (e.g. Formspree) when ready.
      const name = encodeURIComponent(form.name.value.trim());
      const email = encodeURIComponent(form.email.value.trim());
      const message = encodeURIComponent(form.message.value.trim());
      const subject = encodeURIComponent(`Portfolio contact from ${form.name.value.trim()}`);
      const body = `Name: ${form.name.value.trim()}%0AEmail: ${form.email.value.trim()}%0A%0A${message}`;

      window.location.href = `mailto:hello@example.com?subject=${subject}&body=${body}`;

      status.textContent = 'Opening your email app to send the message…';
      status.classList.add('success');
      form.reset();
    });

    // Clear individual field errors as the user types
    ['name', 'email', 'message'].forEach(fieldName => {
      form[fieldName].addEventListener('input', () => setError(fieldName, ''));
    });
  }

});
