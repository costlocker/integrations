// Emulate used bootstrap components with vanilla JS
// Inspired by https://gist.github.com/mpetroff/4666657beeb85754611f

const eventToNavbarButton = (e) => {
  const button = e.target.classList.contains('icon-bar') ? e.target.parentNode : e.target;
  const isNavbarToggleButton =
      button.tagName === 'BUTTON' &&
      button.dataset.target === '#navbar-addon';
  return isNavbarToggleButton
    ? { button, menu: document.getElementById('navbar-addon') }
    : { button: null, menu: null };
};

const toggleNavbar = (button, menu) => {
  var shouldBeOpened = button.classList.contains('collapsed');
  if (shouldBeOpened) {
    menu.classList.add('in');
    button.classList.remove('collapsed');
  } else {
    menu.classList.remove('in');
    button.classList.add('collapsed');
  }
  button.setAttribute('aria-expanded', shouldBeOpened);
};

const listenOnNavbarClick = (e) => {
  const { button, menu } = eventToNavbarButton(e);
  if (!button) {
    return;
  }
  toggleNavbar(button, menu);
};

export default function componentsAdapter() {
  document.addEventListener('click', listenOnNavbarClick);
};
