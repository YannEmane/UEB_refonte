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
	}

	/* ---------- Rubrique active dans le menu ---------- */
	var liensNav = $$('.nav__lien[href^="#"]');
	if (liensNav.length && 'IntersectionObserver' in window) {
		var obsRubrique = new IntersectionObserver(function (entrees) {
			entrees.forEach(function (en) {
				if (!en.isIntersecting) return;
				liensNav.forEach(function (l) { l.classList.toggle('est-active', l.getAttribute('href') === '#' + en.target.id); });
			});
		}, { rootMargin: '-45% 0px -50% 0px' });
		liensNav.forEach(function (l) { var s = $(l.getAttribute('href')); if (s) obsRubrique.observe(s); });
	}

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

	/* ---------- Diaporama de la bannière ---------- */
	var diapo = $('[data-diaporama]');
	if (diapo) {
		var images = $$('[data-diapo]', diapo), puces = $$('[data-puce]', diapo), courant = 0, minuteur = null, DUREE = 7000;
		diapo.style.setProperty('--duree', DUREE + 'ms');
		var montrer = function (n) {
			images[courant].classList.remove('est-active');
			puces[courant].removeAttribute('aria-current');
			courant = (n + images.length) % images.length;
			/* Les images chargées paresseusement sont demandées juste avant leur tour. */
			var img = images[courant].querySelector('img'); img.loading = 'eager';
			images[courant].classList.add('est-active');
			puces.forEach(function (p, i) { p.classList.toggle('est-vue', i < courant); });
			puces[courant].setAttribute('aria-current', 'true');
			programmer();
		};
		var programmer = function () {
			clearTimeout(minuteur);
			if (!calme) minuteur = setTimeout(function () { montrer(courant + 1); }, DUREE);
		};
		puces.forEach(function (p) { p.addEventListener('click', function () { montrer(+p.dataset.puce); }); });
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

	/* ---------- Explorateur « poupée russe » ---------- */
	var source = $('#donnees-formation');
	var explo = $('#explorateur');
	if (source && explo) {
		var arbre = JSON.parse(source.textContent || '[]');
		var index = {};
		(function indexer(noeuds, parents) {
			noeuds.forEach(function (n) { index[n.id] = { n: n, parents: parents }; indexer(n.enfants || [], parents.concat(n)); });
		})(arbre, []);
		var NIVEAUX = ['Établissement', 'Départements', 'Filières', 'Unités d’enseignement'];
		var colonnes = $('[data-x-colonnes]', explo);

		var colonne = function (titre, profondeur, noeuds, actif) {
			var col = doc.createElement('section');
			col.className = 'colonne';
			col.dataset.profondeur = profondeur;
			var html = '<h3 class="colonne__titre">' + (profondeur > 1 ? '<button type="button" class="colonne__retour" data-retour aria-label="Revenir">' + ICONES.gauche + '</button>' : '') + '<span>' + echapper(titre) + '</span><span>' + noeuds.length + '</span></h3>';
			if (!noeuds.length) {
				html += '<p class="colonne__vide">Contenu en cours de publication.</p>';
			} else {
				html += '<ul>';
				noeuds.forEach(function (n, i) {
					var sous = n.enfants && n.enfants.length;
					var detail = profondeur === 3 ? (n.code ? n.code + (n.semestre ? ' · ' + n.semestre : '') : (n.semestre || '')) : (sous ? n.enfants.length + ' ' + (NIVEAUX[profondeur + 1] || '').toLowerCase() : (n.diplome || n.resp || ''));
					html += '<li style="--i:' + i + '"><button type="button" class="noeud" data-noeud="' + n.id + '"' + (actif === n.id ? ' aria-current="true"' : '') + '>' +
						'<span class="noeud__texte"><strong>' + echapper(n.titre) + '</strong>' + (detail ? '<small>' + echapper(detail) + '</small>' : '') + '</span>' +
						(profondeur === 3 && n.credits ? '<span class="noeud__credits" title="Crédits">' + echapper(n.credits) + '</span>' : '') +
						ICONES.droite + '</button></li>';
				});
				html += '</ul>';
			}
			col.innerHTML = html;
			return col;
		};

		var ficheUE = function (n) {
			var col = doc.createElement('section');
			col.className = 'colonne fiche-ue';
			col.dataset.profondeur = 4;
			col.innerHTML =
				'<button type="button" class="colonne__retour" data-retour aria-label="Revenir">' + ICONES.gauche + '</button>' +
				(n.code ? '<span class="fiche-ue__code">' + echapper(n.code) + '</span>' : '') +
				'<h3>' + echapper(n.titre) + '</h3>' +
				'<dl><div><dt>Crédits</dt><dd>' + echapper(n.credits || '—') + '</dd></div><div><dt>Semestre</dt><dd>' + echapper(n.semestre || '—') + '</dd></div><div><dt>Volume</dt><dd style="font-size:1rem">' + echapper(n.volume || '—') + '</dd></div></dl>' +
				(n.texte ? '<p>' + echapper(n.texte) + '</p>' : '') +
				(n.resp ? '<p><strong>Enseignant :</strong> ' + echapper(n.resp) + '</p>' : '') +
				'<div class="fiche-ue__actions">' +
				(n.syllabus ? '<a class="btn btn--plein" href="' + echapper(n.syllabus) + '" target="_blank" rel="noopener">' + ICONES.document + 'Télécharger le syllabus</a>' : '<span class="vide">Syllabus bientôt disponible.</span>') +
				'<a class="btn btn--contour" href="' + echapper(n.url) + '">Fiche complète</a></div>';
			return col;
		};

		/* Affiche le chemin jusqu'au nœud choisi : une colonne par niveau. */
		var afficher = function (id) {
			var e = index[id];
			if (!e) return;
			var chemin = e.parents.concat(e.n);
			var racine = chemin[0];
			explo.style.setProperty('--etab', racine.couleur || '');
			$('[data-x-logo]', explo).src = racine.logo || '';
			$('[data-x-fil]', explo).textContent = racine.sigle + (racine.ville ? ' · ' + racine.ville : '');
			$('[data-x-titre]', explo).textContent = racine.titre;
			var anciennes = $$('.colonne', colonnes).length;
			colonnes.innerHTML = '';
			var derniere;
			chemin.forEach(function (n, p) {
				if (p > 3) return;
				var suivant = chemin[p + 1];
				if (p < 3) {
					derniere = colonne(NIVEAUX[p + 1], p + 1, n.enfants || [], suivant ? suivant.id : null);
					colonnes.appendChild(derniere);
				}
			});
			if (chemin.length === 4) {
				derniere = ficheUE(e.n);
				colonnes.appendChild(derniere);
			}
			/* Seules les nouvelles colonnes s'animent. */
			$$('.colonne', colonnes).forEach(function (c, i) { if (i < anciennes - 1) { c.style.animation = 'none'; $$('li', c).forEach(function (li) { li.style.animation = 'none'; }); } });
			$$('.colonne', colonnes).forEach(function (c) { c.classList.remove('est-courante'); });
			derniere.classList.add('est-courante');
			requestAnimationFrame(function () { colonnes.scrollTo({ left: colonnes.scrollWidth, behavior: calme ? 'auto' : 'smooth' }); });
		};

		doc.addEventListener('click', function (e) {
			var b = e.target.closest('[data-explorer]');
			if (!b) return;
			afficher(+(b.dataset.chemin || b.dataset.explorer));
			explo.showModal();
		});
		explo.addEventListener('click', function (e) {
			if (e.target === explo || e.target.closest('[data-fermer]')) { explo.close(); return; }
			var n = e.target.closest('[data-noeud]');
			if (n) { afficher(+n.dataset.noeud); return; }
			if (e.target.closest('[data-retour]')) {
				var cols = $$('.colonne', colonnes), cour = cols.indexOf($('.est-courante', colonnes));
				if (cour > 0) { cols[cour].classList.remove('est-courante'); cols[cour - 1].classList.add('est-courante'); }
			}
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

	/* ---------- Filtre des actualités par format ---------- */
	var filtresActu = $('[data-filtres-actu]');
	if (filtresActu) {
		filtresActu.addEventListener('click', function (e) {
			var b = e.target.closest('button');
			if (!b) return;
			$$('button', filtresActu).forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
			var f = b.dataset.format, n = 0, visibles = 0;
			$$('[data-fil-actu] [data-format]').forEach(function (el) {
				var garde = !f || el.dataset.format === f;
				el.classList.toggle('est-filtre', !garde);
				if (garde) {
					visibles++;
					if (!calme) el.animate([{ opacity: 0, transform: 'translateY(20px) scale(.98)' }, { opacity: 1, transform: 'none' }], { duration: 550, delay: n++ * 60, easing: 'cubic-bezier(.16,1,.3,1)', fill: 'backwards' });
				}
			});
			var vide = $('[data-vide-filtre]');
			if (vide) vide.hidden = visibles > 0;
		});
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

	/* ---------- Filtre du personnel par établissement ---------- */
	var filtrePerso = $('[data-filtre-personnel]');
	if (filtrePerso) {
		filtrePerso.addEventListener('change', function () {
			var v = filtrePerso.value;
			$$('.accordeon__item').forEach(function (item) {
				var n = 0;
				$$('.membre', item).forEach(function (m) {
					var garde = !v || m.dataset.etab === v;
					m.classList.toggle('est-filtre', !garde);
					if (garde) n++;
				});
				var compte = $('summary small', item);
				if (compte) compte.textContent = n;
			});
		});
	}
})();
