/* Page de gestion du site (/gestion/) : liste, ajout, modification et suppression
   des contenus par l'API REST de WordPress. Chaque rubrique se décrit dans
   RUBRIQUES (champs, point d'accès) : en ajouter une ne demande pas de nouveau code. */
(function () {
	'use strict';

	var C = window.SUEB_GESTION;
	var app = document.getElementById('gestion-app');
	if (!C || !app) return;

	/* ---------- Rubriques ---------- */

	var GROUPES = ['Contenus éditoriaux', 'Structure de l’université', 'Personnes', 'Partenariats', 'Page d’accueil et site'];

	var RUBRIQUES = [
		{
			id: 'actualites', groupe: 0, titre: 'Actualités', endpoint: 'posts', singulier: 'l’actualité',
			champs: [
				{ path: 'title', label: 'Titre', type: 'text', required: true },
				{ path: 'date', label: 'Date de publication', type: 'datetime' },
				{ path: 'status', label: 'Statut', type: 'select', options: [['publish', 'Publié'], ['draft', 'Brouillon']] },
				{ path: 'format', label: 'Format', type: 'select', options: [['standard', 'Article'], ['video', 'Vidéo'], ['audio', 'Audio']], aide: 'Choisir Vidéo ou Audio si l’actualité comporte un fichier ou un lien ci-dessous.' },
				{ path: 'categories', label: 'Rubrique', type: 'taxo', endpoint: 'categories' },
				{ path: 'excerpt', label: 'Résumé', type: 'textarea', aide: 'Affiché à la une et sur l’accueil.' },
				{ path: 'featured_media', label: 'Image à la une', type: 'image' },
				{ path: 'content', label: 'Texte', type: 'richtext' },
				{ path: 'meta._sueb_media', label: 'Fichier vidéo ou audio', type: 'fichier' },
				{ path: 'meta._sueb_media_url', label: 'Ou lien YouTube / Vimeo', type: 'url' },
				{ path: 'meta._sueb_duree', label: 'Durée', type: 'text', aide: 'Ex. 3 min 20.' }
			]
		},
		{ id: 'evenements', groupe: 0, titre: 'Évènements et agenda', bientot: true },
		{ id: 'pages', groupe: 0, titre: 'Pages fixes', bientot: true },
		{ id: 'mediatheque', groupe: 0, titre: 'Médiathèque', bientot: true },
		{ id: 'etablissements', groupe: 1, titre: 'Établissements', bientot: true },
		{ id: 'formations', groupe: 1, titre: 'Départements, filières et UE', bientot: true },
		{ id: 'personnel', groupe: 2, titre: 'Personnel et annuaire', bientot: true },
		{ id: 'dirigeants', groupe: 2, titre: 'Dirigeants du rectorat', bientot: true },
		{ id: 'partenaires', groupe: 3, titre: 'Partenaires et conventions', bientot: true },
		{ id: 'institutions', groupe: 3, titre: 'Universités et villes de la carte', bientot: true },
		{ id: 'accueil-reglages', groupe: 4, titre: 'Accueil (bannière, vidéo, frise)', bientot: true },
		{ id: 'globaux', groupe: 4, titre: 'Coordonnées, liens et menus', bientot: true }
	];

	var STATUTS = { publish: 'Publié', draft: 'Brouillon', future: 'Programmé', pending: 'En attente', private: 'Privé', trash: 'Corbeille' };
	var FORMATS = { standard: 'Article', video: 'Vidéo', audio: 'Audio' };

	var etat = { page: 1, recherche: '', statut: 'any' };
	var listes = {};
	var contenu;

	/* ---------- Outils ---------- */

	/* Barre d'outils de l'éditeur de texte : groupes de boutons.
	   c = commande, t = texte du bouton, l = libellé, e = état suivi (gras, liste…), b = bloc suivi (titre, citation). */
	var OUTILS = [
		[{ c: 'p', t: '¶', l: 'Paragraphe', b: 'p' }, { c: 'h2', t: 'T2', l: 'Titre de section', b: 'h2' }, { c: 'h3', t: 'T3', l: 'Sous-titre', b: 'h3' }, { c: 'h4', t: 'T4', l: 'Petit titre', b: 'h4' }],
		[{ c: 'bold', t: '<b>G</b>', l: 'Gras', e: 'bold' }, { c: 'italic', t: '<i>I</i>', l: 'Italique', e: 'italic' }, { c: 'underline', t: '<u>S</u>', l: 'Souligné', e: 'underline' }, { c: 'strikeThrough', t: '<s>B</s>', l: 'Barré', e: 'strikeThrough' }],
		[{ c: 'insertUnorderedList', t: '• Liste', l: 'Liste à puces', e: 'insertUnorderedList' }, { c: 'insertOrderedList', t: '1. Liste', l: 'Liste numérotée', e: 'insertOrderedList' }, { c: 'blockquote', t: '❝ Citation', l: 'Citation', b: 'blockquote' }],
		[{ c: 'justifyLeft', t: '⇤', l: 'Aligner à gauche', e: 'justifyLeft' }, { c: 'justifyCenter', t: '↔', l: 'Centrer', e: 'justifyCenter' }, { c: 'justifyRight', t: '⇥', l: 'Aligner à droite', e: 'justifyRight' }, { c: 'justifyFull', t: '☰', l: 'Justifier', e: 'justifyFull' }],
		[{ c: 'link', t: 'Lien', l: 'Insérer un lien' }, { c: 'unlink', t: 'Sans lien', l: 'Retirer le lien' }, { c: 'image', t: 'Image', l: 'Insérer une image' }, { c: 'insertHorizontalRule', t: '―', l: 'Ligne de séparation' }],
		[{ c: 'undo', t: '↶', l: 'Annuler' }, { c: 'redo', t: '↷', l: 'Rétablir' }, { c: 'removeFormat', t: 'Effacer', l: 'Effacer la mise en forme' }]
	];

	function barreOutils() {
		return OUTILS.map(function (g) {
			return '<span class="g-riche__groupe" role="group">' + g.map(function (o) {
				var etat = o.e || o.b ? ' aria-pressed="false"' + (o.e ? ' data-etat="' + o.e + '"' : ' data-bloc="' + o.b + '"') : '';
				return '<button type="button" data-cmd="' + o.c + '" title="' + esc(o.l) + '" aria-label="' + esc(o.l) + '"' + etat + '>' + o.t + '</button>';
			}).join('') + '</span>';
		}).join('');
	}

	function garderSelection(zone) {
		var s = window.getSelection();
		if (s.rangeCount && zone.contains(s.anchorNode)) zone._range = s.getRangeAt(0).cloneRange();
	}

	function restaurer(zone, range) {
		zone.focus();
		if (!range) return;
		var s = window.getSelection();
		s.removeAllRanges();
		s.addRange(range);
	}

	function lien(zone) {
		garderSelection(zone);
		var range = zone._range;
		var url = window.prompt('Adresse du lien (https://…, mailto:… ou tel:…)', 'https://');
		if (!url || !/^(https?:\/\/|mailto:|tel:)/i.test(url)) return;
		restaurer(zone, range);
		if (range && range.collapsed) document.execCommand('insertHTML', false, '<a href="' + esc(url) + '">' + esc(url) + '</a>');
		else document.execCommand('createLink', false, url);
	}

	function commande(zone, cmd) {
		var blocs = { p: '<p>', h2: '<h2>', h3: '<h3>', h4: '<h4>', blockquote: '<blockquote>' };
		if (cmd === 'link') return lien(zone);
		if (cmd === 'image') {
			garderSelection(zone);
			zone.parentNode.querySelector('.g-riche__fichier').click();
			return;
		}
		zone.focus();
		if (blocs[cmd]) document.execCommand('formatBlock', false, blocs[cmd]);
		else document.execCommand(cmd, false, null);
		majOutils();
	}

	function insererImage(input) {
		var zone = input.closest('.g-riche').querySelector('.g-riche__zone');
		var fichier = input.files[0];
		input.value = '';
		if (!fichier) return;
		var fd = new FormData();
		fd.append('file', fichier, fichier.name);
		notifier('Envoi de l’image…');
		api('wp/v2/media', { method: 'POST', body: fd }).then(function (res) {
			var m = res.data;
			var tailles = (m.media_details && m.media_details.sizes) || {};
			var src = (tailles.large && tailles.large.source_url) || m.source_url;
			var alt = window.prompt('Description de l’image (pour les personnes malvoyantes)', '') || '';
			restaurer(zone, zone._range);
			document.execCommand('insertHTML', false, '<img src="' + esc(src) + '" alt="' + esc(alt) + '">');
			notifier('Image insérée.');
		}).catch(function (err) {
			notifier('Envoi impossible : ' + err.message, true);
		});
	}

	/* Met en évidence les boutons actifs (gras, liste, titre…) selon la position du curseur. */
	function majOutils() {
		var s = window.getSelection();
		var n = s && s.anchorNode;
		if (!n) return;
		var el = n.nodeType === 1 ? n : n.parentNode;
		var zone = el && el.closest ? el.closest('.g-riche__zone') : null;
		if (!zone) return;
		var bloc = '';
		try { bloc = String(document.queryCommandValue('formatBlock') || '').toLowerCase().replace(/[<>]/g, ''); } catch (e) { /* sans effet */ }
		[].forEach.call(zone.parentNode.querySelectorAll('[data-etat],[data-bloc]'), function (b) {
			var actif = false;
			if (b.hasAttribute('data-etat')) {
				try { actif = document.queryCommandState(b.getAttribute('data-etat')); } catch (e) { /* sans effet */ }
			} else {
				actif = b.getAttribute('data-bloc') === bloc || (b.getAttribute('data-bloc') === 'p' && (bloc === 'div' || bloc === ''));
			}
			b.setAttribute('aria-pressed', actif ? 'true' : 'false');
		});
	}

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	function api(chemin, opts) {
		opts = opts || {};
		var entetes = { 'X-WP-Nonce': C.nonce };
		var corps = opts.body;
		if (corps && !(corps instanceof FormData)) {
			entetes['Content-Type'] = 'application/json';
			corps = JSON.stringify(corps);
		}
		var url = C.root + (C.root.indexOf('?') > -1 ? chemin.replace('?', '&') : chemin);
		return fetch(url, { method: opts.method || 'GET', headers: entetes, body: corps, credentials: 'same-origin' }).then(function (r) {
			return r.json().catch(function () { return {}; }).then(function (d) {
				if (!r.ok) {
					var e = new Error(d.message || 'Erreur ' + r.status);
					e.status = r.status;
					throw e;
				}
				return { data: d, pages: parseInt(r.headers.get('X-WP-TotalPages') || '1', 10), total: parseInt(r.headers.get('X-WP-Total') || '0', 10) };
			});
		});
	}

	function notifier(message, erreur) {
		var t = document.getElementById('g-toast');
		if (!t) return;
		t.textContent = message;
		t.className = 'g-toast est-visible' + (erreur ? ' g-toast--erreur' : '');
		clearTimeout(notifier.t);
		notifier.t = setTimeout(function () { t.className = 'g-toast'; }, erreur ? 6000 : 3000);
	}

	function lire(p, chemin) {
		if (chemin.indexOf('meta.') === 0) return (p.meta || {})[chemin.slice(5)];
		var v = p[chemin];
		return v && typeof v === 'object' && !Array.isArray(v) && 'raw' in v ? v.raw : v;
	}

	function trouver(id) {
		return RUBRIQUES.filter(function (r) { return r.id === id; })[0];
	}

	/* ---------- Coque : barre, menu, zone de contenu ---------- */

	function monter() {
		var menu = GROUPES.map(function (g, i) {
			var items = RUBRIQUES.filter(function (r) { return r.groupe === i; }).map(function (r) {
				return '<li>' + (r.bientot
					? '<span class="g-menu__bientot">' + esc(r.titre) + '<small>bientôt</small></span>'
					: '<a href="#' + r.id + '" data-menu="' + r.id + '">' + esc(r.titre) + '</a>') + '</li>';
			}).join('');
			return '<p class="g-menu__groupe">' + esc(g) + '</p><ul>' + items + '</ul>';
		}).join('');
		app.innerHTML =
			'<div class="g-barre"><span>Connecté : <strong>' + esc(C.nom) + '</strong></span><a href="' + esc(C.deconnexion) + '">Se déconnecter</a></div>' +
			'<div class="g-grille"><nav class="g-menu" aria-label="Rubriques"><a href="#" class="g-menu__accueil" data-menu="">Tableau de bord</a>' + menu + '</nav>' +
			'<section class="g-contenu" id="g-contenu" tabindex="-1"></section></div><div class="g-toast" id="g-toast" role="status" aria-live="polite"></div>';
		contenu = document.getElementById('g-contenu');
	}

	function majMenu(id) {
		[].forEach.call(app.querySelectorAll('[data-menu]'), function (a) {
			if (a.getAttribute('data-menu') === id) a.setAttribute('aria-current', 'page');
			else a.removeAttribute('aria-current');
		});
	}

	/* ---------- Routeur ---------- */

	function route() {
		var h = location.hash.replace(/^#/, '').split('/');
		var r = trouver(h[0]);
		majMenu(r && !r.bientot ? r.id : '');
		window.scrollTo(0, 0);
		if (!r || r.bientot) return tableauDeBord();
		if (h[1] === 'new') return formulaire(r, 0);
		if (h[1]) return formulaire(r, parseInt(h[1], 10));
		liste(r);
	}

	function tableauDeBord() {
		var cartes = RUBRIQUES.map(function (r) {
			return r.bientot
				? '<li class="g-carte g-carte--bientot"><strong>' + esc(r.titre) + '</strong><small>Bientôt disponible</small></li>'
				: '<li class="g-carte"><a href="#' + r.id + '"><strong>' + esc(r.titre) + '</strong><small>Ajouter, modifier, supprimer</small></a></li>';
		}).join('');
		contenu.innerHTML = '<div class="g-tete"><h2>Tableau de bord</h2></div><p class="g-aide">Choisissez une rubrique pour gérer ses contenus. Les autres rubriques seront ouvertes au fur et à mesure.</p><ul class="g-cartes">' + cartes + '</ul>';
	}

	/* ---------- Liste ---------- */

	function liste(r) {
		var statuts = [['any', 'Tous'], ['publish', 'Publiés'], ['draft', 'Brouillons'], ['trash', 'Corbeille']].map(function (s) {
			return '<option value="' + s[0] + '"' + (etat.statut === s[0] ? ' selected' : '') + '>' + s[1] + '</option>';
		}).join('');
		contenu.innerHTML =
			'<div class="g-tete"><h2>' + esc(r.titre) + '</h2><a class="btn btn--plein" href="#' + r.id + '/new">Ajouter</a></div>' +
			'<div class="g-outils"><label class="sr" for="g-q">Rechercher</label><input type="search" id="g-q" placeholder="Rechercher" value="' + esc(etat.recherche) + '">' +
			'<label class="sr" for="g-st">Statut</label><select id="g-st">' + statuts + '</select></div><div id="g-liste" aria-live="polite">Chargement…</div>';

		var minuteur;
		document.getElementById('g-q').addEventListener('input', function (e) {
			clearTimeout(minuteur);
			var v = e.target.value;
			minuteur = setTimeout(function () { etat.recherche = v; etat.page = 1; charger(r); }, 350);
		});
		document.getElementById('g-st').addEventListener('change', function (e) {
			etat.statut = e.target.value;
			etat.page = 1;
			charger(r);
		});
		document.getElementById('g-liste').addEventListener('click', function (e) {
			var b = e.target.closest('[data-act]');
			if (!b) return;
			action(r, b.getAttribute('data-act'), parseInt(b.getAttribute('data-id'), 10), b.getAttribute('data-titre'));
		});
		charger(r);
	}

	function charger(r) {
		var zone = document.getElementById('g-liste');
		if (!zone) return;
		var q = 'wp/v2/' + r.endpoint + '?context=edit&per_page=15&page=' + etat.page + '&status=' + etat.statut + '&orderby=date&order=desc&_fields=id,title,date,status,format,link';
		if (etat.recherche) q += '&search=' + encodeURIComponent(etat.recherche);
		api(q).then(function (res) {
			if (!res.data.length) {
				zone.innerHTML = '<p class="g-vide">Aucun contenu' + (etat.recherche ? ' ne correspond à cette recherche' : '') + '.</p>';
				return;
			}
			var corbeille = etat.statut === 'trash';
			var lignes = res.data.map(function (p) {
				var titre = (p.title && (p.title.raw || p.title.rendered)) || '(sans titre)';
				var date = p.date ? new Date(p.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : '';
				var actions = corbeille
					? '<button type="button" data-act="restaurer" data-id="' + p.id + '">Restaurer</button><button type="button" class="g-danger" data-act="supprimer" data-id="' + p.id + '" data-titre="' + esc(titre) + '">Supprimer définitivement</button>'
					: '<a href="#' + r.id + '/' + p.id + '">Modifier</a>' + (p.status === 'publish' && p.link ? '<a href="' + esc(p.link) + '" target="_blank" rel="noopener">Voir</a>' : '') +
					  '<button type="button" class="g-danger" data-act="corbeille" data-id="' + p.id + '" data-titre="' + esc(titre) + '">Supprimer</button>';
				return '<tr><th scope="row">' + esc(titre) + '</th><td>' + esc(date) + '</td><td><span class="g-etat g-etat--' + esc(p.status) + '">' + esc(STATUTS[p.status] || p.status) + '</span></td><td>' + esc(FORMATS[p.format] || '') + '</td><td class="g-actions">' + actions + '</td></tr>';
			}).join('');
			var pagination = res.pages > 1
				? '<nav class="g-pagination" aria-label="Pages"><button type="button" data-page="-1"' + (etat.page <= 1 ? ' disabled' : '') + '>Précédent</button><span>Page ' + etat.page + ' sur ' + res.pages + '</span><button type="button" data-page="1"' + (etat.page >= res.pages ? ' disabled' : '') + '>Suivant</button></nav>'
				: '';
			zone.innerHTML = '<div class="g-tableau"><table><thead><tr><th scope="col">Titre</th><th scope="col">Date</th><th scope="col">Statut</th><th scope="col">Format</th><th scope="col"><span class="sr">Actions</span></th></tr></thead><tbody>' + lignes + '</tbody></table></div>' + pagination + '<p class="g-total">' + res.total + ' élément' + (res.total > 1 ? 's' : '') + '</p>';
			[].forEach.call(zone.querySelectorAll('[data-page]'), function (b) {
				b.addEventListener('click', function () { etat.page += parseInt(b.getAttribute('data-page'), 10); charger(r); });
			});
		}).catch(function (e) {
			zone.innerHTML = '<p class="g-erreur" role="alert">' + esc(e.message) + '</p>';
		});
	}

	function action(r, act, id, titre) {
		var base = 'wp/v2/' + r.endpoint + '/' + id;
		var tache;
		if (act === 'corbeille') {
			if (!window.confirm('Mettre « ' + titre + ' » à la corbeille ? Vous pourrez le restaurer.')) return;
			tache = api(base, { method: 'DELETE' }).then(function () { notifier('Mis à la corbeille.'); });
		} else if (act === 'restaurer') {
			tache = api(base, { method: 'POST', body: { status: 'draft' } }).then(function () { notifier('Restauré en brouillon.'); });
		} else if (act === 'supprimer') {
			if (!window.confirm('Supprimer définitivement « ' + titre + ' » ? Cette action est irréversible.')) return;
			tache = api(base + '?force=true', { method: 'DELETE' }).then(function () { notifier('Supprimé définitivement.'); });
		}
		if (tache) tache.then(function () { charger(r); }).catch(function (e) { notifier(e.message, true); });
	}

	/* ---------- Formulaire ---------- */

	function chargerListes(r) {
		var attentes = r.champs.filter(function (f) { return f.type === 'taxo' && !listes[f.endpoint]; }).map(function (f) {
			return api('wp/v2/' + f.endpoint + '?per_page=100&_fields=id,name&hide_empty=false').then(function (res) { listes[f.endpoint] = res.data; });
		});
		return Promise.all(attentes);
	}

	function formulaire(r, id) {
		contenu.innerHTML = '<p>Chargement…</p>';
		var lecture = id ? api('wp/v2/' + r.endpoint + '/' + id + '?context=edit') : Promise.resolve({ data: { status: 'publish', format: 'standard', meta: {} } });
		Promise.all([lecture, chargerListes(r)]).then(function (res) {
			dessiner(r, id, res[0].data);
		}).catch(function (e) {
			contenu.innerHTML = '<p class="g-erreur" role="alert">' + esc(e.message) + '</p><p><a href="#' + r.id + '">Retour à la liste</a></p>';
		});
	}

	function enParagraphes(html) {
		html = String(html || '').replace(/<!--[\s\S]*?-->/g, '').trim();
		if (html && !/<(p|div|h[1-6]|ul|ol|blockquote|figure)[\s>]/i.test(html)) {
			html = html.split(/\n{2,}/).map(function (s) { return '<p>' + s.replace(/\n/g, '<br>') + '</p>'; }).join('');
		}
		return html;
	}

	function champ(f, p) {
		var v = lire(p, f.path);
		var id = 'f-' + f.path.replace(/\W/g, '_');
		var nom = ' data-path="' + esc(f.path) + '"';
		var req = f.required ? ' required' : '';
		var aide = f.aide ? '<p class="g-aide" id="' + id + '-aide">' + esc(f.aide) + '</p>' : '';
		var libelle = '<label for="' + id + '">' + esc(f.label) + (f.required ? ' *' : '') + '</label>';
		var h;
		switch (f.type) {
			case 'text':
			case 'url':
				h = libelle + '<input type="' + (f.type === 'url' ? 'url' : 'text') + '" id="' + id + '"' + nom + req + ' value="' + esc(v || '') + '">';
				break;
			case 'textarea':
				h = libelle + '<textarea id="' + id + '"' + nom + ' rows="3">' + esc(v || '') + '</textarea>';
				break;
			case 'datetime':
				h = libelle + '<input type="datetime-local" id="' + id + '"' + nom + ' value="' + esc(String(v || '').slice(0, 16)) + '">';
				break;
			case 'select':
				h = libelle + '<select id="' + id + '"' + nom + '>' + f.options.map(function (o) {
					return '<option value="' + esc(o[0]) + '"' + (v === o[0] ? ' selected' : '') + '>' + esc(o[1]) + '</option>';
				}).join('') + '</select>';
				break;
			case 'taxo':
				h = libelle + '<select id="' + id + '"' + nom + '><option value="">— Aucune —</option>' + (listes[f.endpoint] || []).map(function (o) {
					return '<option value="' + o.id + '"' + ((v || [])[0] === o.id ? ' selected' : '') + '>' + esc(o.name) + '</option>';
				}).join('') + '</select>';
				break;
			case 'richtext':
				h = '<span class="g-label" id="' + id + '-l">' + esc(f.label) + '</span><div class="g-riche"><div class="g-riche__barre" role="toolbar" aria-label="Mise en forme du texte">' + barreOutils() + '</div>' +
					'<input type="file" class="g-riche__fichier" accept="image/*" hidden>' +
					'<div class="g-riche__zone" id="' + id + '" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="' + id + '-l"' + nom + '>' + enParagraphes(v) + '</div></div>';
				break;
			case 'image':
			case 'fichier':
				h = '<span class="g-label">' + esc(f.label) + '</span><div class="g-media" data-type="' + f.type + '"><input type="hidden"' + nom + ' value="' + (parseInt(v, 10) || '') + '">' +
					'<div class="g-media__apercu"></div><label class="g-media__choisir">' + (f.type === 'image' ? 'Choisir une image' : 'Choisir un fichier') +
					'<input type="file" accept="' + (f.type === 'image' ? 'image/*' : 'video/*,audio/*') + '"></label><button type="button" class="g-media__retirer" hidden>Retirer</button><span class="g-media__etat" role="status"></span></div>';
				break;
		}
		return '<div class="g-champ g-champ--' + f.type + '">' + h + aide + '</div>';
	}

	function apercu(zone, media) {
		var cible = zone.querySelector('.g-media__apercu');
		var src = (media.media_details && media.media_details.sizes && media.media_details.sizes.medium && media.media_details.sizes.medium.source_url) || media.source_url;
		cible.innerHTML = (media.mime_type || '').indexOf('image/') === 0
			? '<img src="' + esc(src) + '" alt="">'
			: '<a href="' + esc(media.source_url) + '" target="_blank" rel="noopener">' + esc(String(media.source_url).split('/').pop()) + '</a>';
		zone.querySelector('.g-media__retirer').hidden = false;
	}

	function dessiner(r, id, p) {
		contenu.innerHTML = '<div class="g-tete"><h2>' + (id ? 'Modifier ' : 'Ajouter ') + esc(r.singulier) + '</h2><a href="#' + r.id + '">← Retour à la liste</a></div>' +
			'<form class="g-form" novalidate>' + r.champs.map(function (f) { return champ(f, p); }).join('') +
			'<div class="g-form__actions"><button class="btn btn--plein" type="submit">Enregistrer</button><a class="btn" href="#' + r.id + '">Annuler</a></div></form>';
		var form = contenu.querySelector('form');

		try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) { /* sans effet */ }

		/* Aperçu des médias déjà choisis. */
		[].forEach.call(form.querySelectorAll('.g-media'), function (zone) {
			var mid = parseInt(zone.querySelector('input[type=hidden]').value, 10);
			if (mid) api('wp/v2/media/' + mid + '?context=edit&_fields=id,source_url,mime_type,media_details').then(function (res) { apercu(zone, res.data); }).catch(function () {});
		});

		[].forEach.call(form.querySelectorAll('.g-riche__zone'), function (zone) {
			['keyup', 'mouseup', 'focusout'].forEach(function (ev) {
				zone.addEventListener(ev, function () { garderSelection(zone); });
			});
		});

		form.addEventListener('mousedown', function (e) {
			if (e.target.closest('[data-cmd]')) e.preventDefault(); // garde la sélection dans l'éditeur
		});
		form.addEventListener('click', function (e) {
			var b = e.target.closest('[data-cmd]');
			if (b) {
				commande(b.closest('.g-riche').querySelector('.g-riche__zone'), b.getAttribute('data-cmd'));
				return;
			}
			var x = e.target.closest('.g-media__retirer');
			if (x) {
				var z = x.closest('.g-media');
				z.querySelector('input[type=hidden]').value = '';
				z.querySelector('.g-media__apercu').innerHTML = '';
				x.hidden = true;
			}
		});
		form.addEventListener('change', function (e) {
			if (e.target.classList.contains('g-riche__fichier')) return insererImage(e.target);
			if (e.target.type !== 'file' || !e.target.files[0]) return;
			var zone = e.target.closest('.g-media');
			var etatZone = zone.querySelector('.g-media__etat');
			var fd = new FormData();
			fd.append('file', e.target.files[0], e.target.files[0].name);
			etatZone.textContent = 'Envoi en cours…';
			api('wp/v2/media', { method: 'POST', body: fd }).then(function (res) {
				zone.querySelector('input[type=hidden]').value = res.data.id;
				apercu(zone, res.data);
				etatZone.textContent = '';
			}).catch(function (err) {
				etatZone.textContent = '';
				notifier('Envoi impossible : ' + err.message, true);
			});
			e.target.value = '';
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var titre = form.querySelector('[data-path="title"]');
			if (titre && !titre.value.trim()) {
				notifier('Le titre est obligatoire.', true);
				titre.focus();
				return;
			}
			var bouton = form.querySelector('button[type=submit]');
			bouton.disabled = true;
			api('wp/v2/' + r.endpoint + (id ? '/' + id : ''), { method: 'POST', body: collecter(r, form) }).then(function () {
				notifier('Enregistré.');
				location.hash = '#' + r.id;
			}).catch(function (err) {
				notifier(err.message, true);
				bouton.disabled = false;
			});
		});
	}

	function collecter(r, form) {
		var charge = {};
		var meta = {};
		r.champs.forEach(function (f) {
			var el = form.querySelector('[data-path="' + f.path + '"]');
			if (!el) return;
			var v = f.type === 'richtext' ? el.innerHTML.replace(/^(<p><br><\/p>)+$/, '').trim() : el.value;
			if (f.type === 'image' || f.type === 'fichier') v = parseInt(v, 10) || 0;
			if (f.type === 'taxo') v = v ? [parseInt(v, 10)] : [];
			if (f.type === 'datetime') {
				if (!v) return;
				if (v.length === 16) v += ':00';
			}
			if (f.path.indexOf('meta.') === 0) meta[f.path.slice(5)] = v;
			else charge[f.path] = v;
		});
		if (Object.keys(meta).length) charge.meta = meta;
		return charge;
	}

	/* ---------- Démarrage ---------- */

	monter();
	document.addEventListener('selectionchange', majOutils);
	window.addEventListener('hashchange', route);
	route();
})();