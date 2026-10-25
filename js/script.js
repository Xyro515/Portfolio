'use strict';

const page = document.documentElement;
const themeButton = document.querySelector('.theme-toggle');
const menuButton = document.querySelector('.menu-toggle');
const navigation = document.querySelector('.nav-links');
const header = document.querySelector('.site-header');
const backToTop = document.querySelector('.back-to-top');

function updateThemeButton() {
  if (!themeButton) return;
  const isDark = page.dataset.theme === 'dark';
  themeButton.innerHTML = `<span aria-hidden="true">${isDark ? '☀' : '☾'}</span>`;
  themeButton.setAttribute('aria-label', `Switch to ${isDark ? 'light' : 'dark'} theme`);
}

updateThemeButton();
themeButton?.addEventListener('click', () => {
  page.dataset.theme = page.dataset.theme === 'dark' ? 'light' : 'dark';
  localStorage.setItem('portfolio-theme', page.dataset.theme);
  updateThemeButton();
});

menuButton?.addEventListener('click', () => {
  const isOpen = navigation.classList.toggle('open');
  menuButton.setAttribute('aria-expanded', String(isOpen));
  menuButton.setAttribute('aria-label', `${isOpen ? 'Close' : 'Open'} navigation menu`);
});

navigation?.addEventListener('click', event => {
  if (!event.target.closest('a')) return;
  navigation.classList.remove('open');
  menuButton?.setAttribute('aria-expanded', 'false');
});

document.addEventListener('click', event => {
  if (!navigation?.classList.contains('open') || event.target.closest('.navbar')) return;
  navigation.classList.remove('open');
  menuButton?.setAttribute('aria-expanded', 'false');
});

function handleScroll() {
  const hasScrolled = window.scrollY > 20;
  header?.classList.toggle('scrolled', hasScrolled);
  backToTop?.classList.toggle('visible', window.scrollY > 500);
}

window.addEventListener('scroll', handleScroll, { passive: true });
handleScroll();
backToTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

const revealItems = document.querySelectorAll('.reveal');
if ('IntersectionObserver' in window) {
  const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    });
  }, { threshold: 0.12 });
  revealItems.forEach(item => revealObserver.observe(item));
} else {
  revealItems.forEach(item => item.classList.add('visible'));
}

const sectionLinks = [...document.querySelectorAll('.nav-links a[href^="#"]')];
const sections = sectionLinks.map(link => document.querySelector(link.getAttribute('href'))).filter(Boolean);
if ('IntersectionObserver' in window && sections.length) {
  const sectionObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      sectionLinks.forEach(link => link.classList.toggle('active', link.getAttribute('href') === `#${entry.target.id}`));
    });
  }, { rootMargin: '-30% 0px -55%', threshold: 0 });
  sections.forEach(section => sectionObserver.observe(section));
}

document.querySelectorAll('.filter').forEach(button => {
  button.addEventListener('click', () => {
    const filter = button.dataset.filter;
    document.querySelectorAll('.filter').forEach(item => item.classList.toggle('active', item === button));
    document.querySelectorAll('.project-card').forEach(card => {
      const categories = card.dataset.categories.split(' ');
      card.hidden = filter !== 'all' && !categories.includes(filter);
    });
  });
});

const contactForm = document.querySelector('.contact-form');
const validationRules = {
  name: value => value.length >= 2 ? '' : 'Please enter at least 2 characters.',
  email: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? '' : 'Please enter a valid email address.',
  subject: value => value.length >= 3 ? '' : 'Please enter a clear subject.',
  message: value => value.length >= 10 ? '' : 'Your message must contain at least 10 characters.'
};

function validateField(field) {
  const message = validationRules[field.name](field.value.trim());
  const error = document.getElementById(`${field.name}-error`);
  field.setAttribute('aria-invalid', String(Boolean(message)));
  if (error) error.textContent = message;
  return !message;
}

contactForm?.querySelectorAll('input, textarea').forEach(field => {
  field.addEventListener('blur', () => validateField(field));
  field.addEventListener('input', () => {
    if (field.getAttribute('aria-invalid') === 'true') validateField(field);
  });
});

contactForm?.addEventListener('submit', async event => {
  event.preventDefault();
  const fields = [...contactForm.querySelectorAll('input, textarea')];
  const isValid = fields.map(validateField).every(Boolean);
  const status = contactForm.querySelector('.form-status');
  if (!isValid) {
    fields.find(field => field.getAttribute('aria-invalid') === 'true')?.focus();
    return;
  }

  const submitButton = contactForm.querySelector('[type="submit"]');
  submitButton.disabled = true;
  status.className = 'form-status';
  status.textContent = 'Sending…';
  try {
    const response = await fetch(contactForm.action, { method: 'POST', body: new FormData(contactForm), headers: { Accept: 'application/json' } });
    const responseText = await response.text();
    const contentType = response.headers.get('content-type') || '';
    let result;
    try {
      result = JSON.parse(responseText);
    } catch {
      const isHtml = contentType.includes('text/html') || /^\s*</.test(responseText);
      const message = isHtml
        ? 'The contact server returned an HTML page instead of JSON. Run the site through XAMPP and open it from localhost; static hosting cannot run the PHP endpoint.'
        : `The contact server returned an invalid response (HTTP ${response.status}).`;
      throw new Error(message);
    }
    if (!response.ok || !result.success) throw new Error(result.message || 'The message could not be sent.');
    status.classList.add('success');
    status.textContent = result.message;
    contactForm.reset();
    fields.forEach(field => field.removeAttribute('aria-invalid'));
  } catch (error) {
    status.classList.add('failure');
    status.textContent = error.message === 'Failed to fetch' ? 'The server is unavailable. Open the site through XAMPP and check MySQL.' : error.message;
  } finally {
    submitButton.disabled = false;
  }
});
