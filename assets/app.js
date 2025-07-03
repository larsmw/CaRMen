import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

import './styles/app.css';

const open_btn = document.querySelector('.open-btn');
const close_btn = document.querySelector('.close-btn');
const popup = document.querySelector('.popup');
const main_popup = document.querySelector('.main-popup');

if ( typeof open_btn !== 'undefined' && open_btn !== null &&
     typeof close_btn !== 'undefined' && close_btn !== null &&
     typeof popup !== 'undefined' && popup !== null &&
     typeof main_popup !== 'undefined' && main_popup !== null
   ) {
    open_btn.addEventListener('click', () => {
	      popup.style.display = 'flex';
	      main_popup.style.cssText = 'animation:slide-in .5s ease; animation-fill-mode: forwards;';
    });

    close_btn.addEventListener('click', () => {
	      main_popup.style.cssText = 'animation:slide-out .5s ease; animation-fill-mode: forwards;';
	      setTimeout(() => {
		        popup.style.display = 'none';
	      }, 500);
    });

    window.addEventListener('click', (e) => {
	      if (e.target == document.querySelector('.popup-overlay')) {
		        main_popup.style.cssText = 'animation:slide-out .5s ease; animation-fill-mode: forwards;';
		        setTimeout(() => {
			          popup.style.display = 'none';
		        }, 500);
	      }
    });
}
