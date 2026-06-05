(function () {
  const DB_NAME = 'haipulse';
  const THEMES = ['premium', 'corporate'];

  const getInitialTheme = function () {
    const storedTheme = window.localStorage.getItem('haipulse_theme');
    if (storedTheme && THEMES.includes(storedTheme)) {
      return storedTheme;
    }
    return 'premium';
  };

  const applyTheme = function (themeName) {
    const resolvedTheme = THEMES.includes(themeName) ? themeName : 'premium';
    document.documentElement.setAttribute('data-theme', resolvedTheme);
    window.localStorage.setItem('haipulse_theme', resolvedTheme);
  };

  applyTheme(getInitialTheme());

  const headerActions = document.querySelector('.header-actions');
  if (headerActions && !headerActions.querySelector('[data-theme-switch]')) {
    const select = document.createElement('select');
    select.className = 'theme-switch';
    select.setAttribute('aria-label', 'Select visual theme');
    select.setAttribute('data-theme-switch', '1');
    select.innerHTML = [
      '<option value="premium">Theme: Premium</option>',
      '<option value="corporate">Theme: Corporate</option>'
    ].join('');
    select.value = document.documentElement.getAttribute('data-theme') || 'premium';

    select.addEventListener('change', function () {
      applyTheme(select.value);
    });

    headerActions.insertBefore(select, headerActions.firstChild);
  }

  const filterToggles = document.querySelectorAll('[data-filter-toggle]');
  const filterPanel = document.querySelector('[data-filter-panel]');

  if (filterToggles.length && filterPanel) {
    const setFilterState = function (isOpen) {
      filterPanel.classList.toggle('open', isOpen);
      filterPanel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
      filterToggles.forEach(function (button) {
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    };

    filterToggles.forEach(function (button) {
      button.addEventListener('click', function () {
        const isOpen = !filterPanel.classList.contains('open');
        setFilterState(isOpen);
      });
    });

    filterPanel.addEventListener('click', function (event) {
      if (event.target === filterPanel) {
        setFilterState(false);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        setFilterState(false);
      }
    });

    setFilterState(filterPanel.classList.contains('open'));
  }

  const searchForms = document.querySelectorAll('[data-search-form]');
  searchForms.forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();

      const keyword = form.querySelector('[name="keyword"]')?.value || '';
      const categorySlug = form.querySelector('[name="category_slug"]')?.value || '';
      const emirate = form.querySelector('[name="emirate"]')?.value || '';

      const params = new URLSearchParams();
      params.set('db', DB_NAME);
      if (keyword) params.set('keyword', keyword);
      if (categorySlug) params.set('category_slug', categorySlug);
      if (emirate) params.set('emirate', emirate);

      window.location.href = 'listings.html?' + params.toString();
    });
  });

  const resultHint = document.querySelector('[data-result-hint]');
  const params = new URLSearchParams(window.location.search);

  const syncFieldValue = function (selector, value) {
    const field = document.querySelector(selector);
    if (field && value !== null) {
      field.value = value;
    }
  };

  syncFieldValue('[name="keyword"]', params.get('keyword'));
  syncFieldValue('[name="category_slug"]', params.get('category_slug'));
  syncFieldValue('[name="emirate"]', params.get('emirate'));
  syncFieldValue('[name="sort_by"]', params.get('sort_by'));

  const categoryValues = params.getAll('category_slug[]');
  if (categoryValues.length) {
    document.querySelectorAll('[name="category_slug[]"]').forEach(function (checkbox) {
      checkbox.checked = categoryValues.includes(checkbox.value);
    });
  }

  ['is_open_now', 'is_verified', 'has_home_service', 'has_delivery', 'wheelchair_accessible'].forEach(function (name) {
    const el = document.querySelector('[name="' + name + '"]');
    if (el && params.get(name) === '1') {
      el.checked = true;
    }
  });

  const minRating = params.get('min_rating');
  if (minRating !== null) {
    const ratingInput = document.querySelector('[name="min_rating"][value="' + minRating + '"]');
    if (ratingInput) {
      ratingInput.checked = true;
    }
  }

  if (resultHint) {
    const parts = [];

    if (params.get('keyword')) parts.push('Keyword: ' + params.get('keyword'));
    if (params.get('category_slug')) parts.push('Category: ' + params.get('category_slug'));
    if (params.get('emirate')) parts.push('City: ' + params.get('emirate'));

    resultHint.textContent = parts.length
      ? 'Showing best matches for ' + parts.join(' · ')
      : 'Showing best matches for all UAE business listings.';
  }

  // Client-side search quality guard: block single-letter/non-meaningful searches.
  const hasMeaningfulSearchTerm = function (value) {
    const normalized = String(value || '').trim();
    if (!normalized) {
      return false;
    }
    const alnumOnly = normalized.replace(/[^\p{L}\p{N}]+/gu, '');
    return alnumOnly.length >= 2;
  };

  document.querySelectorAll('form').forEach(function (form) {
    const searchField = form.querySelector('[name="q"], [name="keyword"]');
    if (!searchField) {
      return;
    }

    form.addEventListener('submit', function (event) {
      const raw = String(searchField.value || '').trim();
      if (raw !== '' && !hasMeaningfulSearchTerm(raw)) {
        event.preventDefault();
        const message = 'Please enter at least 2 meaningful characters to search.';
        searchField.setCustomValidity(message);
        if (typeof searchField.reportValidity === 'function') {
          searchField.reportValidity();
        }
      } else {
        searchField.setCustomValidity('');
      }
    });

    searchField.addEventListener('input', function () {
      if (searchField.validationMessage) {
        searchField.setCustomValidity('');
      }
    });
  });
})();
