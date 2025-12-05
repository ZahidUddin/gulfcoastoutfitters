(function () {
  'use strict';

  var BODY_BACKGROUNDS = [
    '#8850FF',
    '#FFBA00',
    '#4054FF'
  ];

  function Slider () {
    this.cards = document.querySelectorAll('.card');
    this.currentIndex = 0;

    this.isDragging = false;
    this.startX = 0;
    this.currentX = 0;

    this.autoplayDelay = 4000; // autoplay interval (ms)
    this.autoplayId = null;

    // basic dimensions / ratio defaults
    this.windowWidth = window.innerWidth;
    if (this.cards.length) {
      this.cardWidth = this.cards[0].offsetWidth || this.windowWidth / 2;
    } else {
      this.cardWidth = this.windowWidth / 2;
    }
    this.ratio = this.windowWidth / (this.cardWidth / 4);

    this.initEvents();
    this.setActivePlaceholder();
    this.startAutoplay();
  }

  // initialize drag events
  Slider.prototype.initEvents = function () {
    document.addEventListener('touchstart', this.onStart.bind(this));
    document.addEventListener('touchmove', this.onMove.bind(this));
    document.addEventListener('touchend', this.onEnd.bind(this));

    document.addEventListener('mousedown', this.onStart.bind(this));
    document.addEventListener('mousemove', this.onMove.bind(this));
    document.addEventListener('mouseup', this.onEnd.bind(this));

    // optional: update dimensions on resize
    window.addEventListener('resize', this.onResize.bind(this));
  };

  Slider.prototype.onResize = function () {
    this.windowWidth = window.innerWidth;
    var card = this.cards[this.currentIndex];
    if (card) {
      this.cardWidth = card.offsetWidth || this.windowWidth / 2;
      this.ratio = this.windowWidth / (this.cardWidth / 4);
    }
  };

  // start autoplay
  Slider.prototype.startAutoplay = function () {
    var self = this;

    if (this.autoplayId) {
      clearInterval(this.autoplayId);
    }

    this.autoplayId = setInterval(function () {
      if (self.isDragging) return;
      self.slideLeft(); // will loop infinitely
    }, this.autoplayDelay);
  };

  // stop autoplay (if you ever need it)
  Slider.prototype.stopAutoplay = function () {
    if (this.autoplayId) {
      clearInterval(this.autoplayId);
      this.autoplayId = null;
    }
  };

  // set active placeholder
  Slider.prototype.setActivePlaceholder = function () {
    var placeholders = document.querySelectorAll('.cards-placeholder__item');
    var activePlaceholder = document.querySelector('.cards-placeholder__item--active');

    if (activePlaceholder) {
      activePlaceholder.classList.remove('cards-placeholder__item--active');
    }

    if (placeholders.length && placeholders[this.currentIndex]) {
      placeholders[this.currentIndex].classList.add('cards-placeholder__item--active');
    }

    var bodyEl = document.querySelector('.mainS');
    if (BODY_BACKGROUNDS[this.currentIndex]) {
      bodyEl.style.backgroundColor = BODY_BACKGROUNDS[this.currentIndex];
    }
  };

  // mousedown / touchstart
  Slider.prototype.onStart = function (evt) {
    if (!this.cards.length) return;

    this.isDragging = true;

    var pageX = evt.pageX;
    if (evt.touches && evt.touches.length) {
      pageX = evt.touches[0].pageX;
    }

    this.currentX = pageX;
    this.startX = this.currentX;

    var card = this.cards[this.currentIndex];

    // calculate ratio to use in parallax effect
    this.windowWidth = window.innerWidth;
    this.cardWidth = card.offsetWidth || this.windowWidth / 2;
    this.ratio = this.windowWidth / (this.cardWidth / 4);
  };

  // mouseup / touchend
  Slider.prototype.onEnd = function (evt) {
    if (!this.isDragging) return;

    this.isDragging = false;

    var diff = this.startX - this.currentX;
    var direction = (diff > 0) ? 'left' : 'right';
    this.startX = 0;

    if (Math.abs(diff) > this.windowWidth / 4) {
      if (direction === 'left') {
        this.slideLeft();
      } else if (direction === 'right') {
        this.slideRight();
      } else {
        this.cancelMoveCard();
      }
    } else {
      this.cancelMoveCard();
    }
  };

  // mousemove / touchmove
  Slider.prototype.onMove = function (evt) {
    if (!this.isDragging || !this.cards.length) return;

    var pageX = evt.pageX;
    if (evt.touches && evt.touches.length) {
      pageX = evt.touches[0].pageX;
    }

    this.currentX = pageX;
    var diff = this.startX - this.currentX;
    diff *= -1;

    // don't let drag away from the center more than quarter of window
    if (Math.abs(diff) > this.windowWidth / 4) {
      if (diff > 0) {
        diff = this.windowWidth / 4;
      } else {
        diff = - this.windowWidth / 4;
      }
    }

    this.moveCard(diff);
  };

  // slide to left direction (∞ loop)
  Slider.prototype.slideLeft = function () {
    if (!this.cards.length) return;

    var self = this;
    var card = this.cards[this.currentIndex];
    var cardWidth = this.windowWidth / 2;

    // move current card out to the left
    card.style.left = '-50%';

    this.resetCardElsPosition();

    // go to next index, wrap if at the end
    this.currentIndex += 1;
    if (this.currentIndex >= this.cards.length) {
      this.currentIndex = 0;
    }

    this.setActivePlaceholder();
    card = this.cards[this.currentIndex];

    // bring new card to center
    card.style.left = '50%';

    // parallax on inner elements
    this.moveCardEls(cardWidth * 3);

    // add delay to resetting position
    setTimeout(function () {
      self.resetCardElsPosition();
    }, 50);
  };

  // slide to right direction (∞ loop)
  Slider.prototype.slideRight = function () {
    if (!this.cards.length) return;

    var self = this;
    var card = this.cards[this.currentIndex];
    var cardWidth = this.windowWidth / 2;

    // move current card out to the right
    card.style.left = '150%';

    this.resetCardElsPosition();

    // go to previous index, wrap if at the start
    this.currentIndex -= 1;
    if (this.currentIndex < 0) {
      this.currentIndex = this.cards.length - 1;
    }

    this.setActivePlaceholder();
    card = this.cards[this.currentIndex];

    // bring new card to center
    card.style.left = '50%';

    // parallax on inner elements
    this.moveCardEls(-cardWidth * 3);

    // add delay to resetting position
    setTimeout(function () {
      self.resetCardElsPosition();
    }, 50);
  };

  // put active card in original position (center)
  Slider.prototype.cancelMoveCard = function () {
    if (!this.cards.length) return;

    var self = this;
    var card = this.cards[this.currentIndex];

    card.style.transition = 'transform 0.5s ease-out';
    card.style.transform = '';

    this.resetCardElsPosition();
  };

  // reset to original position elements of card
  Slider.prototype.resetCardElsPosition = function () {
    if (!this.cards.length) return;

    var self = this;
    var card = this.cards[this.currentIndex];

    var cardLogo     = card.querySelector('.card__logo');
    var cardPrice    = card.querySelector('.card__price');
    var cardTitle    = card.querySelector('.card__title');
    var cardSubtitle = card.querySelector('.card__subtitle');
    var cardImage    = card.querySelector('.card__image');
    var cardWishList = card.querySelector('.card__wish-list');
    var cardCategory = card.querySelector('.card__category');
    var cardWillAnimate = card.querySelectorAll('.card__will-animate');

    // move card elements to original position
    cardWillAnimate.forEach(function (el) {
      el.style.transition = 'transform 0.5s ease-out';
    });

    if (cardLogo)     cardLogo.style.transform = '';
    if (cardPrice)    cardPrice.style.transform = '';
    if (cardTitle)    cardTitle.style.transform = '';
    if (cardSubtitle) cardSubtitle.style.transform = '';
    if (cardImage)    cardImage.style.transform = '';
    if (cardWishList) cardWishList.style.transform = '';
    if (cardCategory) cardCategory.style.transform = '';

    // clear transitions
    setTimeout(function () {
      card.style.transform = '';
      card.style.transition = '';

      cardWillAnimate.forEach(function (el) {
        el.style.transition = '';
      });
    }, 500);
  };

  // slide card while dragging
  Slider.prototype.moveCard = function (diff) {
    if (!this.cards.length) return;

    var card = this.cards[this.currentIndex];

    card.style.transform = 'translateX(calc(' + diff + 'px - 50%))';
    diff *= -1;

    this.moveCardEls(diff);
  };

  // create parallax effect on card elements sliding them
  Slider.prototype.moveCardEls = function (diff) {
    if (!this.cards.length) return;

    var card = this.cards[this.currentIndex];

    var cardLogo     = card.querySelector('.card__logo');
    var cardPrice    = card.querySelector('.card__price');
    var cardTitle    = card.querySelector('.card__title');
    var cardSubtitle = card.querySelector('.card__subtitle');
    var cardImage    = card.querySelector('.card__image');
    var cardWishList = card.querySelector('.card__wish-list');
    var cardCategory = card.querySelector('.card__category');

    if (cardLogo) {
      cardLogo.style.transform = 'translateX(' + (diff / this.ratio) + 'px)';
    }
    if (cardPrice) {
      cardPrice.style.transform = 'translateX(' + (diff / this.ratio) + 'px)';
    }

    if (cardTitle) {
      cardTitle.style.transform = 'translateX(' + (diff / (this.ratio * 0.90)) + 'px)';
    }
    if (cardSubtitle) {
      cardSubtitle.style.transform = 'translateX(' + (diff / (this.ratio * 0.85)) + 'px)';
    }

    if (cardImage) {
      cardImage.style.transform = 'translateX(' + (diff / (this.ratio * 0.35)) + 'px)';
    }

    if (cardWishList) {
      cardWishList.style.transform = 'translateX(' + (diff / (this.ratio * 0.85)) + 'px)';
    }
    if (cardCategory) {
      cardCategory.style.transform = 'translateX(' + (diff / (this.ratio * 0.65)) + 'px)';
    }
  };

  // create slider
  var slider = new Slider();

})();