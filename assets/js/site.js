/* Site UEb : interactions et animations de la page d'accueil. */
(function () {
	'use strict';

	var doc = document;
	var calme = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var $ = function (s, c) { return (c || doc).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); };
	var echapper = function (t) { var d = doc.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; };
	var ICONES = {
		droite: '<svg class="icone" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>',
		gauche: '<svg class="icone" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>',
		document: '<svg class="icone" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M12 11v6M9 14l3 3 3-3"/></svg>',
		lecture: '<svg class="icone icone--lecture" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4.5v15l12-7.5z" fill="currentColor"/></svg>',
		pause: '<svg class="icone" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" fill="currentColor"/></svg>'
	};

	/* ---------- En-tête : compact au défilement, barre de progression ---------- */
	var entete = $('[data-entete]');
	var hautPage = $('[data-haut]');
	var auDefilement = function () {
		var y = window.scrollY;
		if (entete) {
			entete.classList.toggle('est-compacte', y > 60);
			var max = doc.documentElement.scrollHeight - window.innerHeight;
			entete.style.setProperty('--p', max > 0 ? (y / max).toFixed(4) : 0);
		}
		if (hautPage) hautPage.classList.toggle('est-visible', y > window.innerHeight);
	};
	window.addEventListener('scroll', auDefilement, { passive: true });
	auDefilement();
	if (hautPage) hautPage.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: calme ? 'auto' : 'smooth' }); });

	/* ---------- Menu mobile ---------- */
	var boutonMenu = $('[data-menu]');
	var nav = $('#nav');
	if (boutonMenu && nav) {
		var basculer = function (ouvert) {
			nav.classList.toggle('est-ouverte', ouvert);
			entete.classList.toggle('menu-ouvert', ouvert);
			boutonMenu.setAttribute('aria-expanded', ouvert);
			doc.body.style.overflow = ouvert ? 'hidden' : '';
		};
		boutonMenu.addEventListener('click', function () { basculer(!nav.classList.contains('est-ouverte')); });
		nav.addEventListener('click', function (e) {
			var lien = e.target.closest('a');
			if (!lien) return;
			/* Sur mobile, le premier toucher sur une rubrique à sous-menu ouvre celui-ci. */
			var item = lien.closest('.nav__item--sous');
			if (item && lien.classList.contains('nav__lien') && window.innerWidth <= 980 && doc.activeElement !== lien) { e.preventDefault(); lien.focus(); return; }
			basculer(false);
		});
		doc.addEventListener('keydown', function (e) { if (e.key === 'Escape' && nav.classList.contains('est-ouverte')) basculer(false); });
		/* Toucher la partie assombrie de la page (le voile de l'en-tête) referme le menu. */
		entete.addEventListener('click', function (e) { if (e.target === entete && nav.classList.contains('est-ouverte')) basculer(false); });
	}

	/* ---------- Rubrique active dans le menu ---------- */
	var liensNav = $$('.nav__lien[href^="#"]:not([href="#"])');
	var verrouRubrique = 0; // après un clic, le défilement ne change pas la rubrique choisie
	if (liensNav.length && 'IntersectionObserver' in window) {
		var obsRubrique = new IntersectionObserver(function (entrees) {
			if (Date.now() < verrouRubrique) return;
			entrees.forEach(function (en) {
				if (!en.isIntersecting) return;
				liensNav.forEach(function (l) {
					var active = l.getAttribute('href') === '#' + en.target.id;
					/* Changement dû au défilement : la pastille se remplit depuis son centre. */
					if (active && !l.classList.contains('est-active')) { l.style.removeProperty('--ax'); l.style.removeProperty('--ay'); }
					l.classList.toggle('est-active', active);
				});
			});
		}, { rootMargin: '-45% 0px -50% 0px' });
		liensNav.forEach(function (l) { var s = $(l.getAttribute('href')); if (s) obsRubrique.observe(s); });
	}

	/* ---------- Rubriques de l'en-tête : voile depuis le pointeur, légère attraction, sélection au clic ---------- */
	var grandEcran = window.matchMedia('(min-width: 981px)');
	$$('.nav__lien').forEach(function (l) {
		var placer = function (e, prefixe) {
			var r = l.getBoundingClientRect();
			l.style.setProperty(prefixe + 'x', (e.clientX - r.left) + 'px');
			l.style.setProperty(prefixe + 'y', (e.clientY - r.top) + 'px');
		};
		l.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') placer(e, '--m'); });
		l.addEventListener('pointerleave', function (e) {
			if (e.pointerType === 'mouse') placer(e, '--m');
			l.style.removeProperty('--tx');
			l.style.removeProperty('--ty');
		});
		l.addEventListener('pointermove', function (e) {
			if (calme || e.pointerType !== 'mouse' || !grandEcran.matches) return;
			var r = l.getBoundingClientRect();
			l.style.setProperty('--tx', (((e.clientX - r.left) / r.width - .5) * 6).toFixed(1) + 'px');
			l.style.setProperty('--ty', (((e.clientY - r.top) / r.height - .5) * 4).toFixed(1) + 'px');
		});
		l.addEventListener('click', function (e) {
			if (!grandEcran.matches || liensNav.indexOf(l) < 0) return;
			if (e.detail) placer(e, '--a'); else { l.style.removeProperty('--ax'); l.style.removeProperty('--ay'); }
			liensNav.forEach(function (x) { x.classList.toggle('est-active', x === l); });
			verrouRubrique = Date.now() + 1400;
		});
	});

	/* ---------- Apparition au défilement ---------- */
	var aReveler = $$('.revele, .section__tete');
	if ('IntersectionObserver' in window && !calme) {
		var obs = new IntersectionObserver(function (entrees) {
			entrees.forEach(function (en) {
				if (en.isIntersecting) { en.target.classList.add('est-visible'); obs.unobserve(en.target); }
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: .08 });
		aReveler.forEach(function (el) { obs.observe(el); });
	} else {
		aReveler.forEach(function (el) { el.classList.add('est-visible'); });
	}

	/* ---------- Compteurs ---------- */
	var compteurs = $$('[data-compteur]');
	if (compteurs.length && 'IntersectionObserver' in window && !calme) {
		compteurs.forEach(function (c) { c.textContent = c.dataset.compteur >= 1000 ? Math.round(c.dataset.compteur * .985) : '0'; });
		var obsCompteur = new IntersectionObserver(function (entrees) {
			entrees.forEach(function (en) {
				if (!en.isIntersecting) return;
				obsCompteur.unobserve(en.target);
				var el = en.target, fin = +el.dataset.compteur, debut = +el.textContent, t0 = null, duree = 1800;
				var pas = function (t) {
					if (!t0) t0 = t;
					var p = Math.min(1, (t - t0) / duree), e = 1 - Math.pow(1 - p, 4);
					el.textContent = Math.round(debut + (fin - debut) * e);
					if (p < 1) requestAnimationFrame(pas);
				};
				requestAnimationFrame(pas);
			});
		}, { threshold: .6 });
		compteurs.forEach(function (c) { obsCompteur.observe(c); });
	}

	/* ---------- Carrousel de la bannière ---------- */
	var carrousel = $('[data-carrousel]');
	if (carrousel) {
		var diapos = $$('[data-diapo]', carrousel), traits = $$('[data-trait]', carrousel), bouton = $('[data-pause]', carrousel);
		var courant = 0, minuteur = null, DUREE = 7000, enPause = false, clavier = false;
		/* Le défilement tourne tout seul ; le bouton pause l'arrête (le réglage « animations réduites » ne le coupe pas : seuls les effets de transition disparaissent). */
		var programmer = function () {
			clearTimeout(minuteur);
			if (!enPause && !clavier && !doc.hidden && diapos.length > 1) minuteur = setTimeout(function () { montrer(courant + 1); }, DUREE);
		};
		var charger = function (n) {
			var img = diapos[(n + diapos.length) % diapos.length].querySelector('img');
			if (img) img.loading = 'eager';
		};
		var montrer = function (n) {
			if (diapos.length < 2) return;
			diapos[courant].classList.remove('est-active');
			diapos[courant].toggleAttribute('inert', true);
			traits[courant].removeAttribute('aria-current');
			courant = (n + diapos.length) % diapos.length;
			diapos[courant].classList.add('est-active');
			diapos[courant].toggleAttribute('inert', false);
			traits[courant].setAttribute('aria-current', 'true');
			charger(courant + 1);
			programmer();
		};
		charger(1);
		traits.forEach(function (t) { t.addEventListener('click', function () { montrer(+t.dataset.trait); }); });
		if (bouton) {
			bouton.addEventListener('click', function () {
				enPause = !enPause;
				bouton.innerHTML = enPause ? ICONES.lecture : ICONES.pause;
				bouton.setAttribute('aria-label', enPause ? 'Reprendre le défilement' : 'Mettre le défilement en pause');
				programmer();
			});
		}
		/* Au clavier, le défilement attend que la personne ait fini de naviguer. */
		carrousel.addEventListener('focusin', function (e) { if (e.target.matches(':focus-visible')) { clavier = true; clearTimeout(minuteur); } });
		carrousel.addEventListener('focusout', function () { clavier = false; programmer(); });
		carrousel.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight') montrer(courant + 1);
			if (e.key === 'ArrowLeft') montrer(courant - 1);
		});
		/* Glisser du doigt sur mobile. */
		var departX = null;
		carrousel.addEventListener('touchstart', function (e) { departX = e.touches[0].clientX; }, { passive: true });
		carrousel.addEventListener('touchend', function (e) {
			if (departX === null) return;
			var dx = e.changedTouches[0].clientX - departX;
			departX = null;
			if (Math.abs(dx) > 50) montrer(courant + (dx < 0 ? 1 : -1));
		}, { passive: true });
		doc.addEventListener('visibilitychange', function () { if (doc.hidden) clearTimeout(minuteur); else programmer(); });
		programmer();
	}

	/* ---------- Parallaxe et frise ---------- */
	var parallaxes = $$('[data-parallaxe]');
	var frise = $('[data-frise]');
	if (!calme && (parallaxes.length || frise)) {
		var enCours = false;
		var animer = function () {
			enCours = false;
			var h = window.innerHeight;
			parallaxes.forEach(function (el) {
				var r = el.parentElement.getBoundingClientRect();
				if (r.bottom < 0 || r.top > h) return;
				var centre = r.top + r.height / 2 - h / 2;
				el.style.transform = 'translate3d(0,' + (centre * -parseFloat(el.dataset.parallaxe)).toFixed(1) + 'px,0)';
			});
			if (frise) {
				var f = frise.getBoundingClientRect();
				var avance = Math.min(1, Math.max(0, (h * .65 - f.top) / f.height));
				frise.style.setProperty('--avance', avance.toFixed(3));
			}
		};
		window.addEventListener('scroll', function () { if (!enCours) { enCours = true; requestAnimationFrame(animer); } }, { passive: true });
		animer();
	} else if (frise) {
		frise.style.setProperty('--avance', 1);
	}

	/* ---------- Modale vidéo (présentation et actualités vidéo) ---------- */
	var modale = $('#modale-video');
	if (modale) {
		var zone = $('[data-media]', modale), contenuInitial = zone.innerHTML;
		var ouvrir = function (src, integre) {
			if (integre) zone.innerHTML = '<iframe src="' + echapper(integre) + '" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen title="Vidéo"></iframe>';
			else if (src) zone.innerHTML = '<video src="' + echapper(src) + '" controls autoplay playsinline></video>';
			else zone.innerHTML = contenuInitial;
			modale.showModal();
		};
		modale.addEventListener('close', function () { zone.innerHTML = contenuInitial; });
		modale.addEventListener('click', function (e) { if (e.target === modale || e.target.closest('[data-fermer]')) modale.close(); });
		doc.addEventListener('click', function (e) {
			var b = e.target.closest('[data-ouvrir-video]');
			if (b) { e.preventDefault(); ouvrir(modale.dataset.videoSrc, modale.dataset.videoIntegre); return; }
			var a = e.target.closest('[data-video-src]:not(dialog)');
			if (a && (a.dataset.videoSrc || a.dataset.videoIntegre)) { e.preventDefault(); ouvrir(a.dataset.videoSrc, a.dataset.videoIntegre); }
		});
	}

	/* ---------- Filtre des établissements par ville ---------- */
	var filtresVille = $('[data-filtres]');
	if (filtresVille) {
		filtresVille.addEventListener('click', function (e) {
			var b = e.target.closest('button');
			if (!b) return;
			$$('button', filtresVille).forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
			var ville = b.dataset.ville, n = 0;
			$$('.etab').forEach(function (el) {
				var garde = !ville || el.dataset.ville === ville;
				el.classList.toggle('est-filtre', !garde);
				if (garde && !calme) {
					el.animate([{ opacity: 0, transform: 'translateY(24px)' }, { opacity: 1, transform: 'none' }], { duration: 600, delay: n++ * 70, easing: 'cubic-bezier(.16,1,.3,1)', fill: 'backwards' });
				}
			});
		});
	}

	/* ---------- Carrousel des partenaires ---------- */
	$$('[data-fleches]').forEach(function (groupe) {
		var piste = doc.getElementById(groupe.dataset.fleches);
		if (!piste) return;
		var maj = function () {
			var b = $$('button', groupe);
			b[0].disabled = piste.scrollLeft < 8;
			b[1].disabled = piste.scrollLeft + piste.clientWidth > piste.scrollWidth - 8;
		};
		groupe.addEventListener('click', function (e) {
			var b = e.target.closest('button');
			if (!b) return;
			var carte = $('li', piste);
			piste.scrollBy({ left: +b.dataset.sens * (carte ? carte.offsetWidth + 24 : 400), behavior: calme ? 'auto' : 'smooth' });
		});
		piste.addEventListener('scroll', maj, { passive: true });
		maj();
	});

	/* ---------- Actualités : les tuiles de la mosaïque permutent avec la une ----------
	   Comme « La vie sur le campus » du site de préinscription : à tour de rôle, une petite tuile
	   passe à la une et la une prend sa place. Le rythme est donné par la barre de temps de la une
	   (fin de son animation CSS), qui s'arrête au survol, au clavier, hors de l'écran ou sur pause. */
	var mosaiqueActu = $('[data-mosaique-actu]'), donneesActus = $('#donnees-actus'), tempsActus = $('[data-temps-actus]');
	if (mosaiqueActu && donneesActus && tempsActus && !calme) {
		var actus = JSON.parse(donneesActus.textContent || '[]');
		var tuiles = $$('[data-tuile]', mosaiqueActu), pauseActus = $('[data-pause-actus]');
		var ordre = actus.map(function (a, i) { return i; }), tour = 0;
		var video = function (el, a) {
			if (a.media || a.integre) { el.dataset.videoSrc = a.media; el.dataset.videoIntegre = a.integre; }
			else { delete el.dataset.videoSrc; delete el.dataset.videoIntegre; }
		};
		/* La photo passe d'un calque à l'autre (fondu enchaîné, après décodage) ; le texte s'efface, change, puis revient. */
		var montrerActu = function (tuile, a) {
			var calques = $$('.tuile__photo', tuile);
			if (calques.length < 2) {
				var copie = calques[0].cloneNode(false);
				copie.classList.remove('est-active');
				copie.removeAttribute('loading');
				calques[0].after(copie);
				calques.push(copie);
			}
			var active = calques[0].classList.contains('est-active') ? calques[0] : calques[1];
			var suivante = active === calques[0] ? calques[1] : calques[0];
			var jeton = tuile.jeton = (tuile.jeton || 0) + 1;
			var basculer = function () {
				if (jeton !== tuile.jeton) return; // une permutation plus récente a pris le relais
				suivante.classList.add('est-active');
				active.classList.remove('est-active');
			};
			if (suivante.getAttribute('src') !== a.photo) suivante.src = a.photo;
			if (suivante.decode) suivante.decode().then(basculer, basculer); else basculer();
			tuile.classList.add('est-en-transition');
			setTimeout(function () {
				var date = $('[data-t-date]', tuile), rubrique = $('[data-t-rubrique]', tuile), lien = $('[data-t-lien]', tuile), badge = $('[data-t-badge]', tuile);
				date.textContent = a.date;
				date.setAttribute('datetime', a.iso);
				rubrique.textContent = a.rubrique;
				rubrique.hidden = !a.rubrique;
				lien.textContent = a.titre;
				[lien, $('[data-t-cible]', tuile)].forEach(function (el) { el.href = a.url; video(el, a); });
				$('[data-t-resume]', tuile).textContent = a.texte;
				$('[data-t-lire]', tuile).textContent = a.lien;
				badge.innerHTML = a.badge;
				badge.className = 'badge-format badge-format--' + a.format;
				badge.hidden = !a.badge;
				$('[data-t-lecture]', tuile).hidden = a.format !== 'video';
				tuile.classList.remove('est-en-transition');
			}, 300);
		};
		tempsActus.addEventListener('animationend', function () {
			var j = 1 + tour++ % (tuiles.length - 1), x = ordre[0];
			ordre[0] = ordre[j];
			ordre[j] = x;
			montrerActu(tuiles[0], actus[ordre[0]]);
			montrerActu(tuiles[j], actus[ordre[j]]);
			tempsActus.style.animation = 'none';
			void tempsActus.offsetWidth; // relance la barre de temps
			tempsActus.style.animation = '';
		});
		mosaiqueActu.classList.add('est-animee', 'est-hors-champ');
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entrees) {
				mosaiqueActu.classList.toggle('est-hors-champ', !entrees[0].isIntersecting);
			}, { threshold: .35 }).observe(mosaiqueActu);
		} else {
			mosaiqueActu.classList.remove('est-hors-champ');
		}
		if (pauseActus) {
			pauseActus.hidden = false;
			pauseActus.addEventListener('click', function () {
				var enPause = mosaiqueActu.classList.toggle('est-en-pause');
				pauseActus.innerHTML = enPause ? ICONES.lecture : ICONES.pause;
				pauseActus.setAttribute('aria-label', enPause ? 'Reprendre le défilement' : 'Mettre le défilement en pause');
			});
		}
	}

	/* ---------- Lecteurs audio ---------- */
	var format = function (s) { s = Math.floor(s || 0); return Math.floor(s / 60) + ':' + ('0' + s % 60).slice(-2); };
	$$('[data-lecteur]').forEach(function (l) {
		var audio = $('audio', l), bouton = $('.lecteur__bouton', l), barre = $('.lecteur__barre', l), jauge = $('i', barre), temps = $('.lecteur__temps', l);
		bouton.addEventListener('click', function () {
			$$('[data-lecteur] audio').forEach(function (a) { if (a !== audio) a.pause(); });
			if (audio.paused) audio.play(); else audio.pause();
		});
		audio.addEventListener('play', function () { l.classList.add('est-en-lecture'); bouton.innerHTML = ICONES.pause; });
		audio.addEventListener('pause', function () { l.classList.remove('est-en-lecture'); bouton.innerHTML = ICONES.lecture; });
		audio.addEventListener('timeupdate', function () {
			jauge.style.width = (audio.duration ? audio.currentTime / audio.duration * 100 : 0) + '%';
			temps.textContent = format(audio.currentTime);
		});
		barre.addEventListener('click', function (e) {
			if (!audio.duration) return;
			var r = barre.getBoundingClientRect();
			audio.currentTime = (e.clientX - r.left) / r.width * audio.duration;
		});
	});

	/* ---------- Partenariats : la carte et le défilé des médaillons se répondent ----------
	   Survoler un médaillon éclaire la ville du partenaire et son arc ; survoler une ville éclaire ses médaillons.
	   Les familles filtrent le défilé et rétractent les arcs des villes sans partenaire, puis les redessinent. */
	var reseau = $('[data-reseau]');
	if (reseau) {
		var carteR = $('[data-carte]', reseau), medaillons = $$('.medaillon', reseau), filtresR = $('[data-reseau-filtres]', reseau);
		var svgR = carteR && $('svg', carteR), defile = $('[data-defile]', reseau), pauseDefile = $('[data-defile-pause]', reseau);
		var eclairer = function (ville) {
			if (!carteR) return;
			carteR.classList.toggle('a-focus', !!ville);
			$$('[data-ville]', carteR).forEach(function (el) { el.classList.toggle('est-cible', !!ville && el.getAttribute('data-ville') === ville); });
			medaillons.forEach(function (m) { m.classList.toggle('est-cible', !!ville && m.dataset.ville === ville); });
		};
		medaillons.forEach(function (m) {
			m.addEventListener('mouseenter', function () { eclairer(m.dataset.ville); });
			m.addEventListener('mouseleave', function () { eclairer(''); });
			m.addEventListener('focusin', function () { eclairer(m.dataset.ville); });
			m.addEventListener('focusout', function () { eclairer(''); });
		});
		if (carteR) {
			$$('.carte__ville', carteR).forEach(function (g) {
				g.addEventListener('mouseenter', function () { eclairer(g.getAttribute('data-ville')); });
				g.addEventListener('mouseleave', function () { eclairer(''); });
			});
		}
		/* Les points qui circulent (SMIL) s'arrêtent hors de l'écran, et pour qui préfère moins d'animations. */
		if (svgR && svgR.pauseAnimations) {
			if (calme) svgR.pauseAnimations();
			else if ('IntersectionObserver' in window) {
				new IntersectionObserver(function (entrees) {
					if (entrees[0].isIntersecting) svgR.unpauseAnimations(); else svgR.pauseAnimations();
				}).observe(svgR);
			}
		}
		if (pauseDefile && defile) {
			pauseDefile.addEventListener('click', function () {
				var enPause = defile.classList.toggle('est-en-pause');
				pauseDefile.innerHTML = enPause ? ICONES.lecture : ICONES.pause;
				pauseDefile.setAttribute('aria-label', enPause ? 'Reprendre le défilement' : 'Mettre le défilement en pause');
			});
		}
		if (filtresR) {
			filtresR.addEventListener('click', function (e) {
				var b = e.target.closest('button');
				if (!b) return;
				$$('button', filtresR).forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
				var famille = b.dataset.famille, parVille = {}, k = 0;
				medaillons.forEach(function (m) {
					var garde = !famille || m.dataset.famille === famille;
					m.classList.toggle('est-filtre', !garde);
					if (!garde) return;
					if (m.getAttribute('aria-hidden') !== 'true') parVille[m.dataset.ville] = (parVille[m.dataset.ville] || 0) + 1;
					if (!calme) m.animate([{ opacity: 0, transform: 'scale(.6)' }, { opacity: 1, transform: 'none' }], { duration: 600, delay: (k++ % 13) * 50, easing: 'cubic-bezier(.34,1.56,.64,1)', fill: 'backwards' });
				});
				if (!carteR) return;
				carteR.classList.add('est-filtree');
				$$('[data-ville]', carteR).forEach(function (el) { el.classList.toggle('est-eteinte', !parVille[el.getAttribute('data-ville')]); });
				$$('.carte__ville', carteR).forEach(function (g) {
					var n = parVille[g.getAttribute('data-ville')] || 0, t = $('[data-nombre]', g);
					if (t) t.textContent = n > 1 ? n : '';
				});
			});
		}
	}

	/* ---------- Page d'un établissement : niveaux d'une filière en onglets, rubrique lue dans le sommaire ---------- */
	$$('[data-onglets]').forEach(function (bloc) {
		var onglets = $$('[role="tab"]', bloc), panneaux = $$('[data-panneau]', bloc);
		if (onglets.length < 2) return;
		var montrer = function (i, focus) {
			onglets.forEach(function (o, k) {
				o.setAttribute('aria-selected', k === i);
				o.tabIndex = k === i ? 0 : -1;
			});
			panneaux.forEach(function (p, k) {
				p.hidden = k !== i;
				p.classList.toggle('est-montre', k === i && !calme);
			});
			if (focus) onglets[i].focus();
		};
		onglets.forEach(function (o, i) {
			o.addEventListener('click', function () { montrer(i); });
			o.addEventListener('keydown', function (e) {
				var n = onglets.length;
				if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') { e.preventDefault(); montrer((i + (e.key === 'ArrowRight' ? 1 : n - 1)) % n, true); }
				else if (e.key === 'Home' || e.key === 'End') { e.preventDefault(); montrer(e.key === 'Home' ? 0 : n - 1, true); }
			});
		});
		montrer(0);
	});
	/* Départements : arborescence ou liste A–Z ; une filière s'ouvre dans son panneau latéral. */
	var vues = $('[data-vues]');
	if (vues) {
		vues.addEventListener('click', function (e) {
			var b = e.target.closest('[data-vue]');
			if (!b) return;
			$$('[data-vue]', vues).forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
			$$('[data-vue-panneau]').forEach(function (panneau) {
				var montre = panneau.dataset.vuePanneau === b.dataset.vue;
				panneau.hidden = !montre;
				if (montre) panneau.classList.add('est-visible');
				if (montre && !calme) panneau.animate([{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'none' }], { duration: 450, easing: 'cubic-bezier(.16,1,.3,1)' });
			});
		});
	}
	doc.addEventListener('click', function (e) {
		var ouvrir = e.target.closest('[data-ouvrir-filiere]');
		if (ouvrir) {
			var fiche = doc.getElementById(ouvrir.dataset.ouvrirFiliere);
			if (fiche && fiche.showModal) {
				/* une feuille « niveau » ouvre la fiche sur l'onglet de ce niveau */
				var onglet = ouvrir.dataset.niveau ? $$('[role="tab"]', fiche)[+ouvrir.dataset.niveau] : null;
				if (onglet) onglet.click();
				fiche.showModal(); fiche.dataset.retour = ouvrir.dataset.ouvrirFiliere; fiche.retour = ouvrir;
			}
			return;
		}
		var dialogue = e.target.closest('.fiche-filiere');
		if (e.target.closest('[data-fermer-filiere]') || (e.target.classList && e.target.classList.contains('fiche-filiere'))) {
			(dialogue || e.target).close();
		}
	});
	$$('.fiche-filiere').forEach(function (fiche) {
		fiche.addEventListener('close', function () { if (fiche.retour) fiche.retour.focus(); });
	});

	var sommaire = $('[data-etab-nav]');
	if (sommaire && 'IntersectionObserver' in window) {
		var liensSommaire = $$('a[href^="#"]', sommaire);
		var cibles = liensSommaire.map(function (a) { return $(a.getAttribute('href')); }).filter(Boolean);
		/* La pastille glisse sous le lien de la rubrique lue ; la première fois, elle s'y pose sans glisser. */
		var pastille = $('.etab-nav__indicateur', sommaire), lienActif = null;
		var placerPastille = function () {
			if (!pastille || !lienActif) return;
			var premiere = !sommaire.classList.contains('a-indicateur');
			if (premiere) pastille.style.transition = 'opacity .3s';
			pastille.style.setProperty('--x', lienActif.parentNode.offsetLeft + 'px');
			pastille.style.setProperty('--l', lienActif.offsetWidth + 'px');
			if (premiere) { void pastille.offsetWidth; pastille.style.transition = ''; sommaire.classList.add('a-indicateur'); }
		};
		var obsSommaire = new IntersectionObserver(function (entrees) {
			entrees.forEach(function (en) {
				if (!en.isIntersecting) return;
				liensSommaire.forEach(function (a) {
					var actif = a.getAttribute('href') === '#' + en.target.id;
					a.setAttribute('aria-current', actif);
					if (!actif) return;
					lienActif = a;
					placerPastille();
					var bande = a.parentNode.parentNode;
					if (bande.scrollWidth > bande.clientWidth) bande.scrollTo({ left: a.parentNode.offsetLeft - 16, behavior: calme ? 'auto' : 'smooth' });
				});
			});
		}, { rootMargin: '-40% 0px -55% 0px' });
		cibles.forEach(function (c) { obsSommaire.observe(c); });
		window.addEventListener('resize', placerPastille);
	}

	/* Le bandeau s'éloigne au défilement : l'étudiant descend moins vite que la page, le texte s'efface. */
	var teteEtab = $('[data-etab-tete]');
	if (teteEtab && !calme) {
		var teteEnAttente = false;
		var eloignerTete = function () {
			teteEnAttente = false;
			var y = Math.min(1, Math.max(0, window.scrollY / teteEtab.offsetHeight));
			teteEtab.style.setProperty('--y', y.toFixed(3));
		};
		window.addEventListener('scroll', function () { if (!teteEnAttente) { teteEnAttente = true; requestAnimationFrame(eloignerTete); } }, { passive: true });
		eloignerTete();
	}

	/* ---------- Listes en cascade : les éléments entrent l'un après l'autre quand la liste paraît ----------
	   data-cascade="portraits" dévoile aussi les photos, "galerie" les ouvre en rideau, "logos" fait pivoter les logos. */
	var cascades = $$('[data-cascade]');
	var EASE = 'cubic-bezier(.16,1,.3,1)', RESSORT = 'cubic-bezier(.34,1.56,.64,1)';
	var jouerCascade = function (liste) {
		liste.classList.add('est-joue');
		if (calme || !liste.animate) return;
		var effet = liste.dataset.cascade, k = 0;
		$$(':scope > li', liste).forEach(function (li) {
			if (!li.offsetParent) return;
			var delai = Math.min(k++, 12) * 75;
			if (effet === 'galerie') {
				var fig = $('figure', li), img = $('img', li);
				if (fig) fig.animate([{ clipPath: 'inset(100% 0 0 0)' }, { clipPath: 'inset(0 0 0 0)' }], { duration: 1100, delay: delai * 2, easing: 'cubic-bezier(.65,0,.35,1)', fill: 'backwards' });
				if (img) img.animate([{ transform: 'scale(1.3)' }, { transform: 'none' }], { duration: 1600, delay: delai * 2, easing: EASE, fill: 'backwards' });
				return;
			}
			li.animate([{ opacity: 0, transform: 'translateY(28px)' }, { opacity: 1, transform: 'none' }], { duration: 750, delay: delai, easing: EASE, fill: 'backwards' });
			if (effet === 'portraits') {
				var cadre = $('.profil__portrait', li), photo = $('.profil__portrait > img', li);
				if (cadre) cadre.animate([{ clipPath: 'inset(100% 0 0 0)' }, { clipPath: 'inset(0 0 0 0)' }], { duration: 950, delay: delai + 120, easing: 'cubic-bezier(.65,0,.35,1)', fill: 'backwards' });
				if (photo) photo.animate([{ transform: 'scale(1.25)' }, { transform: 'none' }], { duration: 1400, delay: delai + 120, easing: EASE, fill: 'backwards' });
			}
			if (effet === 'logos') {
				var logo = $('.tutelle-carte__logo, .etab-autres__logo', li);
				if (logo) logo.animate([{ opacity: 0, transform: 'scale(.3) rotate(-160deg)' }, { opacity: 1, transform: 'none' }], { duration: 900, delay: delai + 150, easing: RESSORT, fill: 'backwards' });
			}
		});
	};
	if (cascades.length && 'IntersectionObserver' in window) {
		var obsCascade = new IntersectionObserver(function (entrees) {
			entrees.forEach(function (en) {
				if (en.isIntersecting) { jouerCascade(en.target); obsCascade.unobserve(en.target); }
			});
		}, { rootMargin: '0px 0px -10% 0px', threshold: .05 });
		cascades.forEach(function (l) { obsCascade.observe(l); });
	} else {
		cascades.forEach(function (l) { l.classList.add('est-joue'); });
	}

	/* ---------- CV d'un membre du personnel, dans un panneau latéral ----------
	   Le panneau s'ouvre tout de suite, puis reçoit le CV tiré de la fiche du membre.
	   Un clic avec Ctrl, Maj ou la molette ouvre toujours la fiche elle-même. */
	var panneauCv = null, retourCv = null;
	var creerPanneauCv = function () {
		panneauCv = doc.createElement('dialog');
		panneauCv.className = 'panneau-cv';
		panneauCv.setAttribute('aria-labelledby', 'panneau-cv-titre');
		panneauCv.innerHTML = '<button class="panneau-cv__fermer" type="button" aria-label="Fermer le CV" data-fermer-cv><svg class="icone" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button><div class="panneau-cv__contenu" data-contenu-cv></div>';
		doc.body.appendChild(panneauCv);
		panneauCv.addEventListener('click', function (e) {
			if (e.target === panneauCv || e.target.closest('[data-fermer-cv]')) panneauCv.close();
		});
		panneauCv.addEventListener('close', function () { if (retourCv) retourCv.focus(); });
	};
	doc.addEventListener('click', function (e) {
		var lien = e.target.closest('[data-cv-lien]');
		if (!lien || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || !window.fetch || !window.DOMParser) return;
		if (!panneauCv) creerPanneauCv();
		if (!panneauCv.showModal) return;
		e.preventDefault();
		retourCv = lien;
		var carte = lien.closest('.profil'), contenu = $('[data-contenu-cv]', panneauCv);
		var nom = $('.profil__nom', lien);
		panneauCv.style.setProperty('--etab', carte ? getComputedStyle(carte).getPropertyValue('--etab') : '');
		contenu.innerHTML = '<div class="panneau-cv__tete"><h2 id="panneau-cv-titre">' + echapper(nom ? nom.textContent : '') + '</h2></div><p class="panneau-cv__attente">Chargement du CV…</p>';
		panneauCv.showModal();
		panneauCv.scrollTop = 0;
		fetch(lien.href, { credentials: 'same-origin' }).then(function (r) {
			if (!r.ok) throw new Error(r.status);
			return r.text();
		}).then(function (html) {
			var page = new DOMParser().parseFromString(html, 'text/html');
			var tete = page.querySelector('[data-cv-tete]'), portrait = page.querySelector('.fiche-tete__portrait'), infos = page.querySelector('[data-cv-infos]'), cv = page.querySelector('[data-cv]'), entete = page.querySelector('.fiche-tete');
			if (!tete || !cv) throw new Error('CV introuvable');
			var titre = tete.querySelector('h1');
			if (titre) {
				var h2 = doc.createElement('h2');
				h2.id = 'panneau-cv-titre';
				h2.className = titre.className;
				h2.textContent = titre.textContent;
				titre.replaceWith(h2);
			}
			if (entete) panneauCv.setAttribute('style', entete.getAttribute('style') || '');
			contenu.innerHTML = '<div class="panneau-cv__tete">' + (portrait ? '<div class="panneau-cv__portrait profil__portrait">' + portrait.innerHTML + '</div>' : '') + '<div class="panneau-cv__identite">' + tete.innerHTML + '</div></div>'
				+ '<div class="panneau-cv__corps">' + (infos ? infos.outerHTML : '') + cv.outerHTML + '</div>'
				+ '<p class="panneau-cv__pied"><a class="lien-fleche" href="' + echapper(lien.href) + '">Ouvrir la fiche complète' + '<svg class="icone" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></p>';
			if (!calme) $$('.panneau-cv__corps > *, .cv__bloc', contenu).forEach(function (el, k) { el.animate([{ opacity: 0, transform: 'translateY(10px)' }, { opacity: 1, transform: 'none' }], { duration: 450, delay: 60 + k * 50, easing: 'cubic-bezier(.16,1,.3,1)', fill: 'backwards' }); });
		}).catch(function () {
			window.location.href = lien.href;
		});
	});

	/* ---------- Annuaire du personnel : recherche, établissement, catégorie ---------- */
	var annuaire = $('[data-annuaire]');
	if (annuaire) {
		var champPerso = $('[data-annuaire-recherche]', annuaire), etabPerso = $('[data-annuaire-etab]', annuaire), corpsPerso = $('[data-annuaire-corps]', annuaire);
		var comptePerso = $('[data-annuaire-compte]', annuaire), videPerso = $('[data-annuaire-vide]', annuaire);
		var profils = $$('.profil', annuaire), corpsChoisi = '';
		/* Sans accents ni majuscules : « ecole » trouve « École ». */
		var sansAccents = function (t) { return t.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); };
		var filtrer = function () {
			var mots = sansAccents(champPerso ? champPerso.value.trim() : '').split(/\s+/).filter(Boolean);
			var etab = etabPerso ? etabPerso.value : '', actif = mots.length || etab || corpsChoisi, total = 0, k = 0;
			$$('[data-groupe]', annuaire).forEach(function (g) {
				var vus = 0, dansCorps = !corpsChoisi || g.dataset.groupe === corpsChoisi;
				$$('.profil', g).forEach(function (p) {
					var garde = dansCorps && (!etab || p.dataset.etab === etab) && mots.every(function (m) { return p.dataset.recherche.indexOf(m) > -1; });
					var etait = !p.classList.contains('est-filtre');
					p.classList.toggle('est-filtre', !garde);
					if (garde) {
						vus++;
						if (!etait && !calme && k < 12) p.animate([{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'none' }], { duration: 450, delay: k++ * 40, easing: 'cubic-bezier(.16,1,.3,1)', fill: 'backwards' });
					}
				});
				g.hidden = !vus;
				g.classList.toggle('est-deplie', !!actif || g.dataset.ouvert === '1');
				$('[data-groupe-compte]', g).textContent = vus;
				total += vus;
			});
			comptePerso.textContent = actif && total ? total + (total > 1 ? ' membres trouvés' : ' membre trouvé') + ' sur ' + profils.length : '';
			videPerso.hidden = total > 0;
		};
		if (champPerso) champPerso.addEventListener('input', filtrer);
		if (etabPerso) etabPerso.addEventListener('change', filtrer);
		if (corpsPerso) {
			corpsPerso.addEventListener('click', function (e) {
				var b = e.target.closest('button');
				if (!b) return;
				$$('button', corpsPerso).forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
				corpsChoisi = b.dataset.corps;
				filtrer();
			});
		}
		annuaire.addEventListener('click', function (e) {
			var plus = e.target.closest('[data-annuaire-plus]');
			if (plus) {
				var g = plus.closest('[data-groupe]');
				g.dataset.ouvert = '1';
				g.classList.add('est-deplie');
				var premier = $('.profil.est-replie:not(.est-filtre) a', g);
				if (premier) premier.focus();
			}
			if (e.target.closest('[data-annuaire-effacer]')) {
				if (champPerso) champPerso.value = '';
				if (etabPerso) etabPerso.value = '';
				if (corpsPerso) $$('button', corpsPerso).forEach(function (x, i) { x.setAttribute('aria-pressed', i === 0); });
				corpsChoisi = '';
				filtrer();
				if (champPerso) champPerso.focus();
			}
		});
	}
})();