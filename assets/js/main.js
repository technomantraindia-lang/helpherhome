/**
 * HELPER HOME - Main JavaScript Engine
 * Handles navigation, sticky header, accessible accordions,
 * modal dialogs, enquiry forms, and scroll interactions.
 */

document.addEventListener('DOMContentLoaded', () => {
  initStickyHeader();
  initMobileNav();
  initServicesDropdown();
  initHeroSlider();
  initAccordions();
  initEnquiryModal();
  initEnquiryForm();
  initServicePreselection();
  initScrollAnimations();
});

/* ----------------------------------------------------
   1. STICKY HEADER & SCROLL BEHAVIOR
---------------------------------------------------- */
function initStickyHeader() {
  const header = document.getElementById('mainHeader');
  if (!header) return;

  const syncHeaderHeight = () => {
    const h = Math.ceil(header.getBoundingClientRect().height) || 96;
    document.documentElement.style.setProperty('--header-height', `${h}px`);
    if (window.innerWidth <= 768) {
      document.documentElement.style.setProperty('--mobile-header-height', `${h}px`);
    }
  };

  const handleScroll = () => {
    if (window.scrollY > 40) {
      header.classList.add('header-scrolled');
      header.classList.remove('header-transparent');
    } else {
      header.classList.remove('header-scrolled');
      header.classList.add('header-transparent');
    }
    syncHeaderHeight();
  };

  window.addEventListener('scroll', handleScroll, { passive: true });
  window.addEventListener('resize', syncHeaderHeight, { passive: true });
  syncHeaderHeight();
  handleScroll();
}

/* ----------------------------------------------------
   2. DESKTOP SERVICES DROPDOWN
---------------------------------------------------- */
function initServicesDropdown() {
  const dropdownToggle = document.getElementById('servicesMenuBtn');
  const dropdownMenu = document.getElementById('servicesDropdown');
  const navItem = document.getElementById('servicesNavItem');

  if (!dropdownToggle || !dropdownMenu) return;

  let timeoutId = null;

  const openDropdown = () => {
    clearTimeout(timeoutId);
    dropdownToggle.setAttribute('aria-expanded', 'true');
    dropdownMenu.classList.add('is-open', 'opacity-100', 'visible', 'translate-y-0');
    dropdownMenu.classList.remove('opacity-0', 'invisible', 'translate-y-2', 'pointer-events-none');
  };

  const closeDropdown = () => {
    timeoutId = setTimeout(() => {
      dropdownToggle.setAttribute('aria-expanded', 'false');
      dropdownMenu.classList.remove('is-open', 'opacity-100', 'visible', 'translate-y-0');
      dropdownMenu.classList.add('opacity-0', 'invisible', 'translate-y-2', 'pointer-events-none');
    }, 120);
  };

  dropdownToggle.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isExpanded = dropdownToggle.getAttribute('aria-expanded') === 'true';
    if (isExpanded) closeDropdown();
    else openDropdown();
  });

  if (navItem) {
    navItem.addEventListener('mouseenter', openDropdown);
    navItem.addEventListener('mouseleave', closeDropdown);
  }

  document.addEventListener('click', (e) => {
    if (!navItem?.contains(e.target) && dropdownToggle.getAttribute('aria-expanded') === 'true') {
      closeDropdown();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && dropdownToggle.getAttribute('aria-expanded') === 'true') {
      closeDropdown();
      dropdownToggle.focus();
    }
  });
}

/* ----------------------------------------------------
   2.1. CINEMATIC HERO SERVICE SLIDER
---------------------------------------------------- */
function initHeroSlider() {
  const slider = document.getElementById('heroSlider');
  if (!slider) return;

  const slides = Array.from(slider.querySelectorAll('.hero-slide'));
  const tabBtns = Array.from(slider.querySelectorAll('.hero-tab-btn'));
  const prevBtn = document.getElementById('heroPrevBtn');
  const nextBtn = document.getElementById('heroNextBtn');
  const scrollOverlay = document.getElementById('heroScrollOverlay');
  const navContainer = document.getElementById('heroNavContainer');

  if (slides.length === 0) return;

  const DURATION = 6500;
  const WORD_STAGGER = 85;
  let currentIndex = 0;
  let isPaused = false;
  let isTransitioning = false;
  let animFrameId = null;
  let startTime = null;
  let elapsedBeforePause = 0;
  let leaveTimer = null;
  let revealTimers = [];
  const reduceMotionMq = window.matchMedia('(prefers-reduced-motion: reduce)');
  let isReducedMotion = reduceMotionMq.matches;

  const clearRevealTimers = () => {
    revealTimers.forEach((id) => clearTimeout(id));
    revealTimers = [];
  };

  const prepareHeadingWords = (slide) => {
    const heading = slide.querySelector('[data-hero-anim="heading"]');
    if (!heading || heading.dataset.wordsReady === 'true') return;

    heading.querySelectorAll('.hero-line').forEach((line) => {
      const prepared = [];

      Array.from(line.childNodes).forEach((node) => {
        if (node.nodeType === Node.TEXT_NODE) {
          const parts = node.textContent.split(/(\s+)/).filter((part) => part.length);
          if (parts.length) prepared.push({ type: 'text', parts });
          return;
        }

        if (node.nodeType === Node.ELEMENT_NODE && node.matches('[data-hero-highlight], .hero-gold-highlight')) {
          const text = Array.from(node.childNodes)
            .filter((child) => child.nodeType === Node.TEXT_NODE)
            .map((child) => child.textContent)
            .join('')
            .trim();
          prepared.push({
            type: 'highlight',
            text
          });
          return;
        }

        if (node.nodeType === Node.ELEMENT_NODE) {
          prepared.push({ type: 'node', node: node.cloneNode(true) });
        }
      });

      line.textContent = '';

      prepared.forEach((item) => {
        if (item.type === 'text') {
          item.parts.forEach((part) => {
            if (/^\s+$/.test(part)) {
              line.appendChild(document.createTextNode(part));
              return;
            }
            const word = document.createElement('span');
            word.className = 'hero-word';
            word.textContent = part;
            word.style.setProperty('color', '#FFFDF8', 'important');
            word.style.setProperty('-webkit-text-fill-color', '#FFFDF8', 'important');
            line.appendChild(word);
          });
          return;
        }

        if (item.type === 'highlight') {
          const highlight = document.createElement('span');
          highlight.className = 'hero-gold-highlight';
          highlight.setAttribute('data-hero-highlight', '');
          item.text.split(/\s+/).filter(Boolean).forEach((part, idx, arr) => {
            const word = document.createElement('span');
            word.className = 'hero-word is-highlight';
            word.textContent = part + (idx < arr.length - 1 ? '\u00A0' : '');
            highlight.appendChild(word);
          });
          line.appendChild(highlight);
          return;
        }

        if (item.type === 'node') {
          line.appendChild(item.node);
        }
      });
    });

    heading.dataset.wordsReady = 'true';
  };

  slides.forEach(prepareHeadingWords);
  slider.classList.add('hero-ready');

  const resetSlideMotion = (slide) => {
    slide.querySelectorAll('.hero-word').forEach((word) => word.classList.remove('is-in'));
    slide.querySelectorAll('[data-hero-cta]').forEach((cta) => cta.classList.remove('is-in'));
    const content = slide.querySelector('.hero-content, .hero-slide-content');
    const card = slide.querySelector('.hero-floating-card, .hero-info-card');
    if (content) {
      content.style.transform = '';
      content.style.removeProperty('transform');
    }
    if (card) {
      card.style.transform = '';
      card.style.removeProperty('transform');
    }
  };

  const revealSlideMotion = (slide) => {
    clearRevealTimers();
    if (isReducedMotion) {
      slide.querySelectorAll('.hero-word').forEach((word) => word.classList.add('is-in'));
      slide.querySelectorAll('[data-hero-cta]').forEach((cta) => cta.classList.add('is-in'));
      return;
    }

    const normalWords = Array.from(slide.querySelectorAll('.hero-word:not(.is-highlight)'));
    const highlightWords = Array.from(slide.querySelectorAll('.hero-word.is-highlight'));
    const ctas = Array.from(slide.querySelectorAll('[data-hero-cta]'));

    normalWords.forEach((word, idx) => {
      revealTimers.push(setTimeout(() => word.classList.add('is-in'), 180 + idx * WORD_STAGGER));
    });

    const highlightStart = 180 + normalWords.length * WORD_STAGGER + 70;
    highlightWords.forEach((word, idx) => {
      revealTimers.push(setTimeout(() => word.classList.add('is-in'), highlightStart + idx * WORD_STAGGER));
    });

    const ctaStart = highlightStart + Math.max(highlightWords.length, 1) * WORD_STAGGER + 120;
    ctas.forEach((cta, idx) => {
      revealTimers.push(setTimeout(() => cta.classList.add('is-in'), ctaStart + idx * 90));
    });
  };

  const syncTabs = (index) => {
    tabBtns.forEach((btn) => {
      const target = parseInt(btn.dataset.slideTarget, 10);
      const active = target === index;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
      const fill = btn.querySelector('.hero-tab-fill');
      if (fill && !active) fill.style.width = '0%';
    });
  };

  const setProgress = (progress) => {
    tabBtns.forEach((btn) => {
      const target = parseInt(btn.dataset.slideTarget, 10);
      const fill = btn.querySelector('.hero-tab-fill');
      if (!fill) return;
      if (target === currentIndex) {
        fill.style.width = isReducedMotion ? '100%' : `${Math.min(100, progress * 100)}%`;
      } else {
        fill.style.width = '0%';
      }
    });
  };

  const preloadNearby = (index) => {
    const next = slides[(index + 1) % slides.length];
    const img = next?.querySelector('[data-hero-img]');
    if (img && img.loading === 'lazy') {
      const warm = new Image();
      warm.src = img.currentSrc || img.src;
    }
  };

  const tick = (timestamp) => {
    if (!startTime) startTime = timestamp - elapsedBeforePause;
    const currentElapsed = timestamp - startTime;

    if (!isPaused) {
      setProgress(Math.min(1, currentElapsed / DURATION));
      if (currentElapsed >= DURATION) {
        goToSlide((currentIndex + 1) % slides.length);
        return;
      }
    }

    animFrameId = requestAnimationFrame(tick);
  };

  const stopTimer = () => {
    if (animFrameId) {
      cancelAnimationFrame(animFrameId);
      animFrameId = null;
    }
  };

  const startTimer = () => {
    stopTimer();
    startTime = null;
    elapsedBeforePause = 0;
    if (!isPaused) animFrameId = requestAnimationFrame(tick);
  };

  const pause = () => {
    if (isPaused) return;
    isPaused = true;
    if (startTime) elapsedBeforePause = performance.now() - startTime;
    stopTimer();
  };

  const resume = () => {
    if (!isPaused) return;
    isPaused = false;
    startTime = performance.now() - elapsedBeforePause;
    animFrameId = requestAnimationFrame(tick);
  };

  const goToSlide = (newIndex, { force = false } = {}) => {
    if (newIndex < 0) newIndex = slides.length - 1;
    if (newIndex >= slides.length) newIndex = 0;
    if (!force && newIndex === currentIndex) return;
    if (isTransitioning && !force) return;

    isTransitioning = true;
    clearRevealTimers();
    if (leaveTimer) clearTimeout(leaveTimer);

    const prevSlide = slides[currentIndex];
    const nextSlide = slides[newIndex];

    resetSlideMotion(nextSlide);

    if (prevSlide !== nextSlide) {
      prevSlide.classList.remove('is-active');
      prevSlide.classList.add('is-leaving');
      prevSlide.setAttribute('aria-hidden', 'true');
      leaveTimer = setTimeout(() => {
        prevSlide.classList.remove('is-leaving');
        resetSlideMotion(prevSlide);
      }, isReducedMotion ? 450 : 1100);
    }

    currentIndex = newIndex;
    nextSlide.classList.add('is-active');
    nextSlide.setAttribute('aria-hidden', 'false');
    syncTabs(currentIndex);
    setProgress(0);
    revealSlideMotion(nextSlide);
    preloadNearby(currentIndex);
    startTimer();

    setTimeout(() => {
      isTransitioning = false;
    }, isReducedMotion ? 450 : 900);
  };

  tabBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetIndex = parseInt(btn.dataset.slideTarget, 10);
      if (!Number.isNaN(targetIndex)) goToSlide(targetIndex);
    });
  });

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      goToSlide((currentIndex - 1 + slides.length) % slides.length);
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      goToSlide((currentIndex + 1) % slides.length);
    });
  }

  slider.addEventListener('mouseenter', pause);
  slider.addEventListener('mouseleave', resume);
  slider.addEventListener('focusin', pause);
  slider.addEventListener('focusout', (e) => {
    if (!slider.contains(e.relatedTarget)) resume();
  });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) pause();
    else resume();
  });

  slider.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
      e.preventDefault();
      goToSlide((currentIndex - 1 + slides.length) % slides.length);
    } else if (e.key === 'ArrowRight') {
      e.preventDefault();
      goToSlide((currentIndex + 1) % slides.length);
    } else if (e.key === 'Home') {
      e.preventDefault();
      goToSlide(0);
    } else if (e.key === 'End') {
      e.preventDefault();
      goToSlide(slides.length - 1);
    }
  });

  let touchStartX = 0;
  let touchStartY = 0;
  slider.addEventListener('touchstart', (e) => {
    if (e.touches.length === 1) {
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;
      pause();
    }
  }, { passive: true });

  slider.addEventListener('touchend', (e) => {
    if (e.changedTouches.length === 1) {
      const deltaX = e.changedTouches[0].clientX - touchStartX;
      const deltaY = e.changedTouches[0].clientY - touchStartY;
      if (Math.abs(deltaX) > 45 && Math.abs(deltaY) < 60) {
        if (deltaX < 0) goToSlide((currentIndex + 1) % slides.length);
        else goToSlide((currentIndex - 1 + slides.length) % slides.length);
      }
    }
    resume();
  }, { passive: true });

  const onScroll = () => {
    const scrollY = window.scrollY;
    if (scrollY > 400) return;

    const ratio = Math.min(1, Math.max(0, scrollY / 300));

    if (scrollOverlay) {
      scrollOverlay.style.opacity = `${ratio * 0.4}`;
    }

    // Navigation fades slightly but stays anchored (no layout shift)
    if (navContainer) {
      navContainer.style.opacity = `${1 - ratio * 0.35}`;
      navContainer.style.transform = '';
    }

    if (isReducedMotion) return;

    // Only scale background — do NOT offset text (keeps alignment identical)
    const activeSlide = slides[currentIndex];
    const bg = activeSlide?.querySelector('.hero-slide-bg');
    const content = activeSlide?.querySelector('.hero-content, .hero-slide-content');
    if (content) {
      content.style.transform = '';
    }
    // Keep a slight downward bias so faces stay below the header band
    if (bg) bg.style.transform = `scale(${1.06 + ratio * 0.04}) translateY(2.5%)`;
  };

  window.addEventListener('scroll', onScroll, { passive: true });

  const onMotionChange = (e) => {
    isReducedMotion = e.matches;
    if (isReducedMotion) {
      slides.forEach((slide) => {
        const bg = slide.querySelector('.hero-slide-bg');
        const content = slide.querySelector('.hero-slide-content');
        if (bg) bg.style.transform = '';
        if (content) content.style.transform = '';
      });
    }
  };

  if (typeof reduceMotionMq.addEventListener === 'function') {
    reduceMotionMq.addEventListener('change', onMotionChange);
  } else if (typeof reduceMotionMq.addListener === 'function') {
    reduceMotionMq.addListener(onMotionChange);
  }

  // Initial paint
  syncTabs(0);
  revealSlideMotion(slides[0]);
  preloadNearby(0);
  startTimer();
  onScroll();
}

/* ----------------------------------------------------
   3. MOBILE NAVIGATION DRAWER
---------------------------------------------------- */
function initMobileNav() {
  const menuBtn = document.getElementById('mobileMenuBtn');
  const closeBtn = document.getElementById('closeMobileMenuBtn');
  const mobileNav = document.getElementById('mobileNavDrawer');
  const backdrop = document.getElementById('mobileNavBackdrop');
  const submenuToggle = document.getElementById('mobileServicesToggle');
  const submenu = document.getElementById('mobileServicesSubmenu');
  const submenuIcon = document.getElementById('mobileServicesIcon');

  if (!menuBtn || !mobileNav) return;

  const openMobileNav = () => {
    mobileNav.classList.remove('translate-x-full');
    mobileNav.classList.add('translate-x-0');
    if (backdrop) {
      backdrop.classList.remove('opacity-0', 'pointer-events-none');
      backdrop.classList.add('opacity-100');
    }
    document.body.classList.add('mobile-nav-open');
    menuBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMobileNav = () => {
    mobileNav.classList.add('translate-x-full');
    mobileNav.classList.remove('translate-x-0');
    if (backdrop) {
      backdrop.classList.add('opacity-0', 'pointer-events-none');
      backdrop.classList.remove('opacity-100');
    }
    document.body.classList.remove('mobile-nav-open');
    menuBtn.setAttribute('aria-expanded', 'false');
  };

  menuBtn.addEventListener('click', openMobileNav);
  if (closeBtn) closeBtn.addEventListener('click', closeMobileNav);
  if (backdrop) backdrop.addEventListener('click', closeMobileNav);

  // Mobile submenu accordion
  if (submenuToggle && submenu) {
    submenuToggle.addEventListener('click', () => {
      const isExpanded = submenuToggle.getAttribute('aria-expanded') === 'true';
      submenuToggle.setAttribute('aria-expanded', String(!isExpanded));
      if (isExpanded) {
        submenu.classList.add('hidden');
        submenu.classList.remove('is-open');
        if (submenuIcon) submenuIcon.classList.remove('rotate-180');
      } else {
        submenu.classList.remove('hidden');
        submenu.classList.add('is-open');
        if (submenuIcon) submenuIcon.classList.add('rotate-180');
      }
    });
  }

  // Close on link click inside drawer
  const drawerLinks = mobileNav.querySelectorAll('a:not(#mobileServicesToggle)');
  drawerLinks.forEach(link => {
    link.addEventListener('click', closeMobileNav);
  });
}

/* ----------------------------------------------------
   4. ACCESSIBLE ACCORDION (FAQ)
---------------------------------------------------- */
function initAccordions() {
  const accordions = document.querySelectorAll('[data-accordion]');
  accordions.forEach(container => {
    const triggers = container.querySelectorAll('.accordion-trigger');

    triggers.forEach(trigger => {
      trigger.addEventListener('click', () => {
        const item = trigger.closest('.accordion-item');
        const content = item.querySelector('.accordion-content');
        const isExpanded = trigger.getAttribute('aria-expanded') === 'true';

        // Close siblings if in single-open accordion
        const isSingle = container.dataset.accordion === 'single';
        if (isSingle) {
          container.querySelectorAll('.accordion-item').forEach(otherItem => {
            if (otherItem !== item) {
              otherItem.classList.remove('active');
              const otherTrigger = otherItem.querySelector('.accordion-trigger');
              const otherContent = otherItem.querySelector('.accordion-content');
              if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
              if (otherContent) otherContent.style.maxHeight = null;
            }
          });
        }

        // Toggle clicked item
        if (isExpanded) {
          item.classList.remove('active');
          trigger.setAttribute('aria-expanded', 'false');
          content.style.maxHeight = null;
        } else {
          item.classList.add('active');
          trigger.setAttribute('aria-expanded', 'true');
          content.style.maxHeight = content.scrollHeight + 'px';
        }
      });
    });
  });
}

/* ----------------------------------------------------
   5. ENQUIRY MODAL + FORM (SMTP via api/send-enquiry.php)
---------------------------------------------------- */
function siteRootPrefix() {
  const path = window.location.pathname.replace(/\\/g, '/');
  return path.includes('/services/') ? '../' : '';
}

function enquiryApiUrl() {
  return `${siteRootPrefix()}api/send-enquiry.php`;
}

function enquiryFieldMarkup(idPrefix) {
  const p = idPrefix;
  return `
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label for="${p}-fullName" class="enquiry-label">Full Name <span>*</span></label>
        <input type="text" id="${p}-fullName" name="fullName" required autocomplete="name" placeholder="e.g. Ramesh Sharma" class="enquiry-input">
      </div>
      <div>
        <label for="${p}-phone" class="enquiry-label">Phone Number <span>*</span></label>
        <input type="tel" id="${p}-phone" name="phone" required autocomplete="tel" placeholder="e.g. 9876543210" class="enquiry-input">
      </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label for="${p}-email" class="enquiry-label">Email Address <span>*</span></label>
        <input type="email" id="${p}-email" name="email" required autocomplete="email" placeholder="e.g. ramesh@example.com" class="enquiry-input">
      </div>
      <div>
        <label for="${p}-cityArea" class="enquiry-label">City / Area <span>*</span></label>
        <input type="text" id="${p}-cityArea" name="cityArea" required placeholder="e.g. Naranpura, Ahmedabad" class="enquiry-input">
      </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label for="${p}-serviceRequired" class="enquiry-label">Service Required <span>*</span></label>
        <select id="${p}-serviceRequired" name="serviceRequired" required class="enquiry-input">
          <option value="" disabled selected>-- Select a Service --</option>
          <option value="maid">Maid Service</option>
          <option value="servant">Servant Service</option>
          <option value="babysitter">Babysitter Service</option>
          <option value="japa-maid">Japa Maid / Nanny</option>
          <option value="elderly-care">Elderly Caretaker</option>
          <option value="patient-care">Patient Caretaker</option>
          <option value="cook">Cook Service</option>
          <option value="driver">Driver Service</option>
          <option value="domestic-couple">Domestic Couple Service</option>
        </select>
      </div>
      <div>
        <label for="${p}-startDate" class="enquiry-label">Preferred Start Date</label>
        <input type="date" id="${p}-startDate" name="startDate" class="enquiry-input">
      </div>
    </div>
    <div>
      <label for="${p}-requirements" class="enquiry-label">Household Requirements &amp; Timings</label>
      <textarea id="${p}-requirements" name="requirements" rows="3" placeholder="Working hours, home size, family needs..." class="enquiry-input"></textarea>
    </div>
    <input type="text" name="website" tabindex="-1" autocomplete="off" class="enquiry-honeypot" aria-hidden="true">
  `;
}

function validateEnquiryPayload(payload) {
  if (!payload.fullName || payload.fullName.length < 2) {
    return 'Please enter your full name.';
  }
  if (!/^[0-9+\-\s]{10,15}$/.test(payload.phone || '')) {
    return 'Please enter a valid 10-digit mobile number.';
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(payload.email || '')) {
    return 'Please enter a valid email address.';
  }
  if (!payload.cityArea || payload.cityArea.length < 2) {
    return 'Please enter your city or locality.';
  }
  if (!payload.serviceRequired) {
    return 'Please select the service you require.';
  }
  return '';
}

async function submitEnquiry(payload, { submitBtn, errorBox, successBox, form, onSuccess }) {
  if (errorBox) {
    errorBox.classList.add('hidden');
    errorBox.textContent = '';
  }
  if (successBox) successBox.classList.add('hidden');

  const errorMessage = validateEnquiryPayload(payload);
  if (errorMessage) {
    if (errorBox) {
      errorBox.textContent = errorMessage;
      errorBox.classList.remove('hidden');
    } else {
      alert(errorMessage);
    }
    return false;
  }

  const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Sending Enquiry...';
  }

  try {
    const res = await fetch(enquiryApiUrl(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload)
    });

    let data = {};
    try {
      data = await res.json();
    } catch (_) {
      data = {};
    }

    if (!res.ok || !data.ok) {
      throw new Error(data.error || 'Unable to send enquiry. Please try again.');
    }

    if (form) form.reset();
    if (successBox) {
      successBox.classList.remove('hidden');
      successBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    if (typeof onSuccess === 'function') onSuccess(data);
    return true;
  } catch (err) {
    const msg = err.message || 'Unable to send enquiry. Please call +91 98798 88478.';
    if (errorBox) {
      errorBox.textContent = msg;
      errorBox.classList.remove('hidden');
    } else {
      alert(msg);
    }
    return false;
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    }
  }
}

function readEnquiryForm(form) {
  const get = (name) => (form.querySelector(`[name="${name}"]`)?.value || '').trim();
  return {
    fullName: get('fullName'),
    phone: get('phone'),
    email: get('email'),
    cityArea: get('cityArea'),
    serviceRequired: get('serviceRequired'),
    startDate: get('startDate'),
    requirements: get('requirements'),
    website: get('website')
  };
}

function initEnquiryModal() {
  if (document.getElementById('enquiryModal')) return;

  if (!document.getElementById('enquiryModalStyles')) {
    const link = document.createElement('link');
    link.id = 'enquiryModalStyles';
    link.rel = 'stylesheet';
    link.href = `${siteRootPrefix()}assets/css/enquiry-modal.css?v=1`;
    document.head.appendChild(link);
  }

  const modal = document.createElement('div');
  modal.id = 'enquiryModal';
  modal.className = 'enquiry-modal';
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="enquiry-modal__backdrop" data-enquiry-close tabindex="-1"></div>
    <div class="enquiry-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="enquiryModalTitle">
      <button type="button" class="enquiry-modal__close" data-enquiry-close aria-label="Close enquiry form">&times;</button>
      <div class="enquiry-modal__header">
        <p class="enquiry-modal__eyebrow">Helper Home</p>
        <h2 id="enquiryModalTitle" class="enquiry-modal__title">Book a Service</h2>
        <p class="enquiry-modal__subtitle">Share your details and our team will contact you shortly.</p>
        <p class="enquiry-modal__contact">
          <a href="tel:+919879888478">+91 98798 88478</a>
          ·
          <a href="mailto:helperhomeahmedabad@gmail.com">helperhomeahmedabad@gmail.com</a>
        </p>
      </div>
      <div id="enquiryModalError" class="enquiry-alert enquiry-alert--error hidden" role="alert"></div>
      <div id="enquiryModalSuccess" class="enquiry-alert enquiry-alert--success hidden" role="status">
        Enquiry sent. You will also receive a confirmation email shortly.
      </div>
      <form id="enquiryModalForm" class="enquiry-modal__form" novalidate>
        ${enquiryFieldMarkup('modal')}
        <button type="submit" class="btn-gold enquiry-modal__submit">Send Enquiry</button>
        <p class="enquiry-modal__note">Emails go to Helper Home via secure SMTP. You will get a copy at your email.</p>
      </form>
    </div>
  `;
  document.body.appendChild(modal);

  const form = modal.querySelector('#enquiryModalForm');
  const errorBox = modal.querySelector('#enquiryModalError');
  const successBox = modal.querySelector('#enquiryModalSuccess');
  const serviceSelect = modal.querySelector('#modal-serviceRequired');
  let lastFocus = null;

  const openModal = (serviceValue = '') => {
    lastFocus = document.activeElement;
    successBox.classList.add('hidden');
    errorBox.classList.add('hidden');
    if (serviceValue && serviceSelect) {
      serviceSelect.value = serviceValue;
    }
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('enquiry-modal-open');
    const first = modal.querySelector('input, select, textarea, button');
    first?.focus();
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('enquiry-modal-open');
    if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
  };

  window.HelperHomeEnquiry = { open: openModal, close: closeModal };

  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-enquiry-open], .js-open-enquiry');
    if (!trigger) return;
    e.preventDefault();
    const service = trigger.getAttribute('data-service') || '';
    openModal(service);
  });

  modal.addEventListener('click', (e) => {
    if (e.target.closest('[data-enquiry-close]')) {
      e.preventDefault();
      closeModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = form.querySelector('button[type="submit"]');
    await submitEnquiry(readEnquiryForm(form), {
      submitBtn,
      errorBox,
      successBox,
      form,
      onSuccess: () => {
        setTimeout(() => closeModal(), 2200);
      }
    });
  });
}

function initEnquiryForm() {
  const form = document.getElementById('enquiryForm');
  if (!form) return;

  // Ensure honeypot exists on contact page form
  if (!form.querySelector('[name="website"]')) {
    const hp = document.createElement('input');
    hp.type = 'text';
    hp.name = 'website';
    hp.tabIndex = -1;
    hp.autocomplete = 'off';
    hp.className = 'enquiry-honeypot';
    hp.setAttribute('aria-hidden', 'true');
    form.appendChild(hp);
  }

  const submitBtn = form.querySelector('button[type="submit"]');
  const successBox = document.getElementById('formSuccessMessage');
  const errorBox = document.getElementById('formErrorMessage');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    await submitEnquiry(readEnquiryForm(form), {
      submitBtn,
      errorBox,
      successBox,
      form
    });
  });
}

/* ----------------------------------------------------
   6. PRESELECT SERVICE FROM URL PARAMS
---------------------------------------------------- */
function initServicePreselection() {
  const serviceSelect = document.getElementById('serviceRequired');
  if (!serviceSelect) return;

  const urlParams = new URLSearchParams(window.location.search);
  const serviceParam = urlParams.get('service');

  if (serviceParam) {
    for (const option of serviceSelect.options) {
      if (option.value.toLowerCase() === serviceParam.toLowerCase()) {
        serviceSelect.value = option.value;
        break;
      }
    }
  }
}

/* ----------------------------------------------------
   7. SCROLL ANIMATIONS & INTERSECTION OBSERVER
---------------------------------------------------- */
function initScrollAnimations() {
  // Respect prefers-reduced-motion
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  // Check if GSAP and ScrollTrigger are available
  if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);

    // Fade up animations
    const revealElements = document.querySelectorAll('[data-reveal]');
    revealElements.forEach(el => {
      gsap.fromTo(el, 
        { opacity: 0, y: 30 },
        {
          opacity: 1,
          y: 0,
          duration: 0.85,
          ease: 'power2.out',
          immediateRender: false,
          scrollTrigger: {
            trigger: el,
            start: 'top 88%',
            toggleActions: 'play none none none',
            once: true
          }
        }
      );
    });

    // Staggered cards
    const cardGrids = document.querySelectorAll('[data-stagger-grid]');
    cardGrids.forEach(grid => {
      const cards = grid.children;
      gsap.fromTo(cards,
        { opacity: 0, y: 40 },
        {
          opacity: 1,
          y: 0,
          duration: 0.7,
          stagger: 0.12,
          ease: 'power2.out',
          immediateRender: false,
          scrollTrigger: {
            trigger: grid,
            start: 'top 85%',
            once: true
          }
        }
      );
    });

    // Process line draw animation
    const processLine = document.querySelector('.process-line-fill');
    if (processLine) {
      gsap.fromTo(processLine,
        { width: '0%' },
        {
          width: '100%',
          duration: 1.2,
          ease: 'power1.inOut',
          scrollTrigger: {
            trigger: '.process-container',
            start: 'top 75%',
            once: true
          }
        }
      );
    }
  } else {
    // Lightweight IntersectionObserver fallback
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('opacity-100', 'translate-y-0');
          entry.target.classList.remove('opacity-0', 'translate-y-8');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('[data-reveal], [data-stagger-grid] > *').forEach(el => {
      el.classList.add('transition-all', 'duration-700', 'opacity-0', 'translate-y-8');
      observer.observe(el);
    });
  }
}
