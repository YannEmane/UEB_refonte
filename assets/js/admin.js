/* Page de gestion du site (/gestion/) : liste, ajout, modification et suppression
   des contenus par l'API REST de WordPress. Chaque rubrique se décrit dans
   RUBRIQUES (champs, point d'accès, colonnes) ; les champs complémentaires
   viennent de sueb_champs() (inc/champs.php) par SUEB_GESTION.champs. */
(function () {
	'use strict';

	var C = window.SUEB_GESTION;
	var app = document.getElementById('gestion-app');
	if (!C || !app) return;

	/* ---------- Champs et colonnes communs ---------- */

	var STATUTS = { publish: 'Publié', draft: 'Brouillon', future: 'Programmé', pending: 'En attente', private: 'Privé', trash: 'Corbeille' };
	var FORMATS = { standard: 'Article', video: 'Vidéo', audio: 'Audio' };
	var NIVEAUX = C.niveaux || ['Établissement', 'Département', 'Filière', 'Unité d’enseignement'];

	var F = {
		titre: function (label) { return { path: 'title', label: label || 'Titre', type: 'text', required: true }; },
		statut: { path: 'status', label: 'Statut', type: 'select', options: [['publish', 'Publié'], ['draft', 'Brouillon']] },
		resume: function (aide) { return { path: 'excerpt', label: 'Résumé', type: 'textarea', aide: aide }; },
		image: function (label) { return { path: 'featured_media', label: label || 'Image à la une', type: 'image' }; },
		texte: function (label) { return { path: 'content', label: label || 'Texte', type: 'richtext' }; },
		ordre: { path: 'menu_order', label: 'Ordre d’affichage', type: 'number', aide: 'Les plus petits nombres viennent en premier.' }
	};

	/* Fichiers acceptés par les champs « media » de sueb_champs(), selon leur clé. */
	var ACCEPTE = { logo: 'image/*', photo_droite: 'image/*', photo_etudiant: 'image/*', campus_1: 'image/*', campus_2: 'image/*', campus_3: 'image/*', campus_4: 'image/*', syllabus: 'application/pdf', cv: 'application/pdf', media: 'video/*,audio/*' };

	/* Niveaux (0 établissement … 3 UE) où chaque champ d'une structure a un sens. */
	var NIVEAUX_CHAMPS = { sigle: [0, 1], ville: [0], couleur: [0], logo: [0], photo_etudiant: [0], intro_formations: [0], intro_tutelle: [0], intro_equipe: [0], intro_campus: [0], campus_1: [0], legende_1: [0], campus_2: [0], legende_2: [0], campus_3: [0], legende_3: [0], campus_4: [0], legende_4: [0], responsable: [0, 1, 2], diplome: [2], niveaux: [2], parcours: [2], accroche: [2], contact_courriel: [0, 2], contact_telephone: [0, 2], adresse: [0], coordonnees: [0], mission: [0], ecole_doctorale: [0], enseignants: [3], code: [3], credits: [3], semestre: [3], volume: [3], elements: [3], syllabus: [3] };

	/** Les champs complémentaires d'un type, précédés d'un intertitre (`section` : un autre intertitre, ou false pour aucun). `cles` limite la sélection. */
	function metas(type, cles, section) {
		var d = (C.champs || {})[type];
		if (!d) return [];
		var liste = d.champs.filter(function (c) { return !cles || cles.indexOf(c.cle) > -1; });
		var tete = section === false ? [] : [section || { type: 'section', label: d.titre, aide: d.aide }];
		return tete.concat(liste.map(function (c) {
			var f = { path: 'meta._sueb_' + c.cle, cle: c.cle, label: c.label, type: c.type, aide: c.aide, options: c.options };
			if (c.type === 'media') {
				f.accept = ACCEPTE[c.cle] || '';
				f.type = f.accept === 'image/*' ? 'image' : 'fichier';
			}
			if (c.type === 'datetime-local') f.type = 'datetime';
			if (type === 'ueb_structure' && NIVEAUX_CHAMPS[c.cle]) f.niveaux = NIVEAUX_CHAMPS[c.cle];
			return f;
		}));
	}

	function m(p, cle) { return (p.meta || {})['_sueb_' + cle] || ''; }

	function dateFr(v, heure) {
		if (!v) return '';
		var d = new Date(v);
		if (isNaN(d)) return v;
		var o = { day: 'numeric', month: 'long', year: 'numeric' };
		if (heure) { o.hour = '2-digit'; o.minute = '2-digit'; }
		return d.toLocaleDateString('fr-FR', o);
	}

	var COL = {
		date: { t: 'Date', court: true, v: function (p) { return dateFr(p.date); } },
		format: { t: 'Format', court: true, v: function (p) { return FORMATS[p.format] || ''; } },
		une: { t: 'À la une', court: true, v: function (p) { return p.sticky ? '★ À la une' : ''; } },
		ordre: { t: 'Ordre', court: true, v: function (p) { return String(p.menu_order || 0); } },
		meta: function (t, cle) {
			return { t: t, v: function (p) { return m(p, cle); } };
		}
	};

	/* ---------- Rubriques ---------- */

	var GROUPES = ['Contenus éditoriaux', 'Structure de l’université', 'Personnes', 'Partenariats', 'Page d’accueil et site', 'Administration'];

	/* ---------- Droits du compte (inc/roles.php) ----------
	   Chaque rubrique demande une permission ; le serveur vérifie de toute façon chaque action et la portée. */
	var DROITS = C.droits || [];
	var PERM = { actualites: 'sueb_actualites', evenements: 'sueb_evenements', pages: 'sueb_pages', etablissements: 'sueb_etablissements', formations: 'sueb_formations', personnel: 'sueb_personnel', dirigeants: 'sueb_dirigeants', partenaires: 'sueb_partenaires', institutions: 'sueb_partenaires', 'accueil-reglages': 'sueb_reglages', 'etudiants-reglages': 'sueb_reglages', contacts: 'sueb_reglages', globaux: 'sueb_reglages', comptes: 'sueb_comptes' };
	function a(perm) { return DROITS.indexOf(perm) > -1; }
	function permis(id) { return !PERM[id] || a(PERM[id]); }
	var TOUS = C.portee === 'tous';

	/* ---------- Icônes (traits, 24 × 24) ---------- */

	var ICONES = {
		tableau: '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
		journal: '<path d="M4 5a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v14a2 2 0 0 0 2 2H6a2 2 0 0 1-2-2z"/><path d="M17 9h3v10a2 2 0 0 1-2 2M8 8h5M8 12h5M8 16h3"/>',
		agenda: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
		page: '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
		image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>',
		batiment: '<path d="M3 21h18M5 21V8l7-5 7 5v13"/><path d="M9 21v-6h6v6M9 10h.01M15 10h.01"/>',
		diplome: '<path d="m22 10-10-5L2 10l10 5z"/><path d="M6 12v5c3 2 9 2 12 0v-5M22 10v5"/>',
		personnes: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6"/>',
		medaille: '<circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 7 5-3 5 3-1.5-7"/>',
		lien: '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>',
		carte: '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2z"/><path d="M9 4v14M15 6v14"/>',
		maison: '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10M10 20v-6h4v6"/>',
		contact: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		plus: '<path d="M12 5v14M5 12h14"/>',
		recherche: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		sortie: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
		externe: '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>',
		menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
		poubelle: '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>',
		crayon: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
		info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
		envoi: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>',
		gauche: '<path d="M19 12H5M12 19l-7-7 7-7"/>',
		droite: '<path d="M5 12h14M12 5l7 7-7 7"/>',
		fermer: '<path d="M18 6 6 18M6 6l12 12"/>',
		cle: '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.8-9.8M17 6l3 3M14.5 8.5l2 2"/>',
		/* Éditeur de texte */
		'al-gauche': '<path d="M4 6h16M4 10h10M4 14h16M4 18h10"/>',
		'al-centre': '<path d="M4 6h16M7 10h10M4 14h16M7 18h10"/>',
		'al-droite': '<path d="M4 6h16M10 10h10M4 14h16M10 18h10"/>',
		'al-justifie': '<path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>',
		puces: '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1" fill="currentColor"/><circle cx="4.5" cy="12" r="1" fill="currentColor"/><circle cx="4.5" cy="18" r="1" fill="currentColor"/>',
		numeros: '<path d="M10 6h10M10 12h10M10 18h10M4 4.5h1.5V9M4 9h3M4 14.5c.4-.6 1-1 1.7-1 .8 0 1.3.5 1.3 1.2 0 1.2-3 2-3 3.3h3"/>',
		'retrait-plus': '<path d="M4 5h16M11 10h9M11 14h9M4 19h16M4 9.5l3 2.5-3 2.5"/>',
		'retrait-moins': '<path d="M4 5h16M11 10h9M11 14h9M4 19h16M7 9.5 4 12l3 2.5"/>',
		citation: '<path d="M10 7H6a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3v1a3 3 0 0 1-3 3M20 7h-4a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3v1a3 3 0 0 1-3 3"/>',
		'sans-lien': '<path d="M10 13a5 5 0 0 0 7.5.5l1-1M14 11a5 5 0 0 0-7.5-.5l-1 1M4 4l16 16"/>',
		document: '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M12 11v6M9 14l3 3 3-3"/>',
		video: '<rect x="3" y="5" width="13" height="14" rx="2"/><path d="m16 10 5-3v10l-5-3"/>',
		tableau2: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M3 15h18M9 4v16M15 4v16"/>',
		separateur: '<path d="M3 12h18M8 7h8M8 17h8"/>',
		annuler: '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
		retablir: '<path d="m15 14 5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"/>',
		gomme: '<path d="m7 21-4.3-4.3a1 1 0 0 1 0-1.4L13 5a1 1 0 0 1 1.4 0l5.6 5.6a1 1 0 0 1 0 1.4L12 20"/><path d="M7 21h14M8.5 10.5l6 6"/>',
		couleur: '<path d="m5 19 7-15 7 15M8 13h8"/>',
		surligner: '<path d="m9 11-6 6v3h9l3-3M22 12l-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"/>',
		guillemets: '<path d="m8 7-4 5 4 5M13 7l-4 5 4 5M16 7l4 5-4 5"/>',
		'plein-ecran': '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
		'ecran-normal': '<path d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5"/>'
	};

	function ico(nom, classe) {
		return '<svg class="g-ico' + (classe ? ' ' + classe : '') + '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' + (ICONES[nom] || '') + '</svg>';
	}

	/* Icône et description de chaque rubrique (menu, tableau de bord, en-têtes). */
	var PRESENTATION = {
		actualites: ['journal', 'Articles, vidéos et audios publiés dans les Actualités.'],
		evenements: ['agenda', 'Les dates de l’agenda affichées sur l’accueil.'],
		pages: ['page', 'Pages fixes : histoire, message du Recteur, mentions…'],
		mediatheque: ['image', 'Images, vidéos et documents envoyés sur le site.'],
		etablissements: ['batiment', 'Facultés et écoles : identité, couleur, logo, présentation.'],
		formations: ['diplome', 'Départements, filières et unités d’enseignement.'],
		personnel: ['personnes', 'Fiches de l’annuaire : fonction, grade, parcours, CV.'],
		dirigeants: ['medaille', 'Recteur, vice-recteurs et dirigeants du rectorat.'],
		partenaires: ['lien', 'Conventions et partenaires de l’université.'],
		institutions: ['carte', 'Universités partenaires et villes placées sur la carte.'],
		'accueil-reglages': ['maison', 'Bannière, citation du Recteur, frise et vidéo de l’accueil.'],
		'etudiants-reglages': ['personnes', 'Présentation, rubriques et photo de la page Vie étudiante.'],
		contacts: ['carte', 'Rectorat, services centraux et établissements : contacts et position sur la carte de la page Contact.'],
		globaux: ['contact', 'Boîte postale, téléphone, courriel, plateformes et réseaux.'],
		comptes: ['cle', 'Rôles, permissions et comptes de la gestion.']
	};
	function icone(r) { return (PRESENTATION[r.id] || ['page'])[0]; }
	function description(r) { return (PRESENTATION[r.id] || ['', ''])[1]; }

	/** En-tête d'un écran : titre, sous-titre, actions ; `retour` = [lien, libellé] pour un fil de retour. */
	function entete(titre, sousTitre, actions, retour) {
		return '<div class="g-tete"><div class="g-tete__texte">' +
			(retour ? '<a class="g-retour" href="' + retour[0] + '">' + ico('gauche') + esc(retour[1]) + '</a>' : '') +
			'<h2>' + esc(titre) + '</h2>' + (sousTitre ? '<p class="g-sous-titre">' + esc(sousTitre) + '</p>' : '') + '</div>' +
			(actions ? '<div class="g-tete__actions">' + actions + '</div>' : '') + '</div>';
	}

	var RUBRIQUES = [
		{
			id: 'actualites', groupe: 0, titre: 'Actualités', endpoint: 'posts', singulier: 'l’actualité',
			colonnes: [COL.date, COL.une, COL.format],
			champs: [
				F.titre(),
				{ path: 'date', label: 'Date de publication', type: 'datetime' },
				F.statut,
				{ path: 'sticky', label: 'À la une', type: 'case', texte: 'Mettre cet article à la une', aide: 'Les articles à la une défilent dans la bannière de la page d’accueil (les plus récents d’abord).' },
				{ path: 'format', label: 'Format', type: 'select', options: [['standard', 'Article'], ['video', 'Vidéo'], ['audio', 'Audio']], aide: 'Choisir Vidéo ou Audio si l’actualité comporte un fichier ou un lien ci-dessous.' },
				{ path: 'categories', label: 'Rubrique', type: 'taxo', endpoint: 'categories' },
				F.resume('Affiché à la une et sur l’accueil.'),
				F.image(),
				F.texte()
			].concat(metas('post'))
		},
		{
			id: 'evenements', groupe: 0, titre: 'Évènements et agenda', endpoint: 'ueb_evenement', singulier: 'l’évènement',
			colonnes: [{ t: 'Début', court: true, v: function (p) { return dateFr(m(p, 'debut'), true); } }, COL.meta('Lieu', 'lieu')],
			champs: [F.titre(), F.statut].concat(metas('ueb_evenement'), [F.resume('Affiché dans l’agenda de l’accueil.'), F.image(), F.texte('Description')])
		},
		{
			id: 'pages', groupe: 0, titre: 'Pages fixes', endpoint: 'pages', singulier: 'la page', ordre: 'menu_order', pages: true,
			colonnes: [{ t: 'Rôle sur le site', v: function (p) { return (p.sueb_gestion && p.sueb_gestion.role) || ''; } }, COL.ordre],
			champs: [F.titre(), F.statut, F.ordre, F.image(), F.texte('Contenu')]
		},
		{
			id: 'mediatheque', groupe: 0, titre: 'Médiathèque', endpoint: 'media', singulier: 'le fichier', mediatheque: true,
			champs: [
				{ path: 'apercu', label: 'Aperçu', type: 'apercu' },
				F.titre(),
				{ path: 'alt_text', label: 'Texte alternatif', type: 'text', aide: 'Pour une image : ce qu’elle montre, lu aux personnes malvoyantes.' },
				{ path: 'caption', label: 'Légende', type: 'textarea' },
				{ path: 'description', label: 'Description', type: 'textarea' }
			]
		},
		{
			id: 'etablissements', groupe: 1, titre: 'Établissements', endpoint: 'ueb_structure', singulier: 'l’établissement',
			filtre: '&parent=0', ordre: 'menu_order', arbre: true, niveauFixe: 0, defauts: { parent: 0 },
			colonnes: [COL.meta('Sigle', 'sigle'), COL.meta('Ville', 'ville'), COL.ordre],
			champs: [F.titre('Intitulé'), F.statut, F.ordre].concat(
				metas('ueb_structure', ['sigle', 'ville', 'couleur', 'logo', 'responsable']),
				[{ type: 'section', label: 'Présentation', aide: 'Le texte de la section « Présentation » de sa page ; le résumé s’affiche dans les listes.' }, F.resume('Courte présentation affichée dans les listes.'), F.texte('Présentation')],
				[{ type: 'section', label: 'Photos', aide: 'La photo principale et la seconde photo habillent sa carte sur l’accueil ; la photo du bandeau et celles du campus, sa page.' }, F.image('Photo principale')],
				metas('ueb_structure', ['photo_droite', 'photo_etudiant', 'campus_1', 'legende_1', 'campus_2', 'legende_2', 'campus_3', 'legende_3', 'campus_4', 'legende_4'], false),
				metas('ueb_structure', ['intro_formations', 'intro_tutelle', 'intro_equipe', 'intro_campus'], { type: 'section', label: 'Textes des sections de sa page', aide: 'Laissez un champ vide pour garder le texte par défaut.' }),
				metas('ueb_structure', ['mission', 'ecole_doctorale'], { type: 'section', label: 'Missions et formation doctorale', aide: 'Affichées sur la page de l’établissement. Laissez vide pour garder les textes livrés avec le site.' }),
				metas('ueb_structure', ['contact_courriel', 'contact_telephone', 'adresse', 'coordonnees'], { type: 'section', label: 'Contact', aide: 'Les coordonnées et la position affichées sur la page Contact du site.' })
			)
		},
		{
			id: 'formations', groupe: 1, titre: 'Départements, filières et UE', endpoint: 'ueb_structure', singulier: 'la structure',
			filtre: '&parent_exclude=0', ordre: 'menu_order', arbre: true,
			colonnes: [
				{ t: 'Niveau', v: function (p) { var n = arbre && arbre.par[p.id]; return n ? NIVEAUX[Math.min(3, n.prof)] : ''; } },
				{ t: 'Rattaché à', v: function (p) { var n = arbre && arbre.par[p.parent]; return n ? n.nom : ''; } },
				COL.ordre
			],
			champs: [
				F.titre('Intitulé'),
				{ path: 'parent', label: 'Rattaché à', type: 'parent', required: true, genre: true },
				F.statut, F.ordre
			].concat(
				metas('ueb_structure', ['sigle', 'responsable', 'parcours', 'niveaux', 'diplome', 'accroche', 'contact_courriel', 'contact_telephone', 'code', 'credits', 'semestre', 'volume', 'elements', 'syllabus', 'enseignants', 'photo_droite'], { type: 'section', label: 'Détails', aide: 'Seuls les champs utiles à ce que vous ajoutez (département, filière ou unité d’enseignement) sont affichés.' }),
				[F.resume('Pour une filière : le texte à côté de l’accroche, en haut de sa page.'), F.image('Photo principale (pour une filière : le bandeau et la photo de sa page)'), F.texte('Présentation (pour une filière : la section « À propos de la filière »)')]
			)
		},
		{
			id: 'personnel', groupe: 2, titre: 'Personnel et annuaire', endpoint: 'ueb_personnel', singulier: 'le membre du personnel',
			ordre: 'menu_order', arbre: true,
			colonnes: [COL.meta('Fonction', 'fonction'), COL.meta('Grade', 'grade'), COL.ordre],
			champs: [
				F.titre('Nom complet'), F.statut,
				{ path: 'ueb_corps', label: 'Catégories du personnel', type: 'taxo', endpoint: 'ueb_corps', multiple: true },
				F.ordre, F.image('Photo')
			].concat(metas('ueb_personnel'), [F.texte('Présentation')])
		},
		{
			id: 'dirigeants', groupe: 2, titre: 'Dirigeants du rectorat', endpoint: 'ueb_dirigeant', singulier: 'le dirigeant',
			ordre: 'menu_order',
			colonnes: [COL.meta('Fonction', 'sigle'), COL.ordre],
			champs: [F.titre('Nom complet (ex. Pr Jean Dupont)'), F.statut, F.ordre, F.image('Photo')].concat(metas('ueb_dirigeant'), [F.texte('Mot ou présentation')])
		},
		{
			id: 'partenaires', groupe: 3, titre: 'Partenaires et conventions', endpoint: 'ueb_partenaire', singulier: 'le partenaire',
			ordre: 'menu_order',
			colonnes: [COL.meta('Type', 'type'), COL.meta('Ville', 'ville'), COL.ordre],
			champs: [F.titre('Nom du partenaire'), F.statut, F.ordre].concat(metas('ueb_partenaire'), [F.resume(), F.image(), F.texte('Présentation de la convention')])
		},
		{ id: 'institutions', groupe: 3, titre: 'Universités et villes de la carte', carte: true },
		{ id: 'accueil-reglages', groupe: 4, titre: 'Accueil (bannière, vidéo, frise)', reglages: 'accueil' },
		{ id: 'etudiants-reglages', groupe: 4, titre: 'Vie étudiante', reglages: 'etudiants' },
		{ id: 'contacts', groupe: 4, titre: 'Contacts et sites', contacts: true },
		{ id: 'globaux', groupe: 4, titre: 'Coordonnées et liens', reglages: 'coordonnees' },
		{ id: 'comptes', groupe: 5, titre: 'Rôles et comptes', comptes: true }
	].filter(function (r) { return permis(r.id); });

	var etat = { page: 1, recherche: '', statut: 'any', rubrique: '' };
	var listes = {};
	var arbre = null;
	var contenu;

	/* ---------- Outils ---------- */

	/* Barre d'outils de l'éditeur de texte, par groupes.
	   c = commande, i = icône (ou t = texte), l = libellé, e = état suivi (gras, liste, alignement…),
	   v = valeur passée à la commande. Le type de bloc (paragraphe, titres, citation) est une liste déroulante. */
	var BLOCS = [['p', 'Paragraphe'], ['h2', 'Titre de section'], ['h3', 'Sous-titre'], ['h4', 'Petit titre'], ['blockquote', 'Citation']];
	var OUTILS = [
		[{ c: 'bold', t: '<b>G</b>', l: 'Gras (Ctrl+B)', e: 'bold' }, { c: 'italic', t: '<i>I</i>', l: 'Italique (Ctrl+I)', e: 'italic' }, { c: 'underline', t: '<u>S</u>', l: 'Souligné (Ctrl+U)', e: 'underline' }, { c: 'strikeThrough', t: '<s>B</s>', l: 'Barré', e: 'strikeThrough' }, { c: 'superscript', t: 'x<sup>2</sup>', l: 'Exposant (ex. 1er, 2e)', e: 'superscript' }, { c: 'subscript', t: 'x<sub>2</sub>', l: 'Indice (ex. CO2)', e: 'subscript' }],
		[{ c: 'palette-couleur', i: 'couleur', l: 'Couleur du texte', menu: 'couleur' }, { c: 'palette-surligner', i: 'surligner', l: 'Surligner', menu: 'surligner' }],
		[{ c: 'justifyLeft', i: 'al-gauche', l: 'Aligner à gauche', e: 'justifyLeft' }, { c: 'justifyCenter', i: 'al-centre', l: 'Centrer', e: 'justifyCenter' }, { c: 'justifyRight', i: 'al-droite', l: 'Aligner à droite', e: 'justifyRight' }, { c: 'justifyFull', i: 'al-justifie', l: 'Justifier le texte', e: 'justifyFull' }],
		[{ c: 'insertUnorderedList', i: 'puces', l: 'Liste à puces', e: 'insertUnorderedList' }, { c: 'insertOrderedList', i: 'numeros', l: 'Liste numérotée', e: 'insertOrderedList' }],
		[{ c: 'link', i: 'lien', l: 'Insérer un lien (Ctrl+K)' }, { c: 'unlink', i: 'sans-lien', l: 'Retirer le lien' }, { c: 'image', i: 'image', l: 'Insérer une image' }, { c: 'document', i: 'document', l: 'Joindre un document (PDF, Word…)' }, { c: 'video', i: 'video', l: 'Insérer une vidéo YouTube ou Vimeo' }, { c: 'table', i: 'tableau2', l: 'Insérer un tableau' }, { c: 'insertHorizontalRule', i: 'separateur', l: 'Ligne de séparation' }, { c: 'guillemets', i: 'guillemets', l: 'Guillemets français « »' }],
		[{ c: 'undo', i: 'annuler', l: 'Annuler (Ctrl+Z)' }, { c: 'redo', i: 'retablir', l: 'Rétablir (Ctrl+Y)' }, { c: 'removeFormat', i: 'gomme', l: 'Effacer la mise en forme' }],
		[{ c: 'plein-ecran', i: 'plein-ecran', l: 'Plein écran', e: 'plein-ecran' }]
	];
	/* Couleurs de la charte, lisibles sur blanc. */
	var COULEURS = {
		couleur: [['#15231b', 'Noir (par défaut)'], ['#1d5a38', 'Vert UEb'], ['#9a6b00', 'Or foncé'], ['#1f6f94', 'Bleu'], ['#b3261e', 'Rouge'], ['#6a1b6d', 'Violet'], ['#4f5d55', 'Gris']],
		surligner: [['transparent', 'Sans surlignage'], ['#fff3b0', 'Jaune'], ['#d8f0dc', 'Vert'], ['#d6ecf7', 'Bleu'], ['#fde2e0', 'Rose']]
	};

	function barreOutils() {
		var bloc = '<span class="g-riche__groupe" role="group"><select class="g-riche__bloc" data-bloc-choix title="Type de texte" aria-label="Type de texte">' +
			BLOCS.map(function (b) { return '<option value="' + b[0] + '">' + esc(b[1]) + '</option>'; }).join('') + '</select></span>';
		return bloc + OUTILS.map(function (g) {
			return '<span class="g-riche__groupe" role="group">' + g.map(function (o) {
				var etat = o.e ? ' aria-pressed="false" data-etat="' + o.e + '"' : '';
				var menu = '';
				if (o.menu) {
					etat = ' aria-haspopup="true" aria-expanded="false"';
					menu = '<span class="g-riche__palette" hidden>' + COULEURS[o.menu].map(function (c) {
						var cmd = o.menu === 'couleur' ? 'foreColor' : 'hiliteColor';
						return '<button type="button" data-cmd="' + cmd + '" data-valeur="' + c[0] + '" title="' + esc(c[1]) + '" aria-label="' + esc(c[1]) + '" style="--pastille:' + c[0] + '"></button>';
					}).join('') + '</span>';
				}
				return '<span class="g-riche__outil"><button type="button" data-cmd="' + o.c + '" title="' + esc(o.l) + '" aria-label="' + esc(o.l) + '"' + etat + '>' + (o.i ? ico(o.i) : o.t) + '</button>' + menu + '</span>';
			}).join('') + '</span>';
		}).join('');
	}

	/* Nombre de mots et temps de lecture, sous l'éditeur. */
	function compter(zone) {
		var pied = zone.parentNode.querySelector('.g-riche__pied');
		if (!pied) return;
		var mots = (zone.innerText || '').trim().split(/\s+/).filter(Boolean).length;
		pied.textContent = mots + ' mot' + (mots > 1 ? 's' : '') + ' · ' + Math.max(1, Math.round(mots / 220)) + ' min de lecture';
	}

	/* Texte collé (Word, page web…) : on garde la structure (titres, gras, listes, liens, tableaux)
	   et on retire le reste (polices, couleurs, classes, styles), qui casserait la mise en page du site. */
	var GARDER = { P: 1, H2: 1, H3: 1, H4: 1, B: 1, STRONG: 1, I: 1, EM: 1, U: 1, S: 1, SUP: 1, SUB: 1, A: 1, UL: 1, OL: 1, LI: 1, BLOCKQUOTE: 1, BR: 1, TABLE: 1, THEAD: 1, TBODY: 1, TR: 1, TH: 1, TD: 1, HR: 1 };
	function nettoyerCollage(html) {
		var doc = new DOMParser().parseFromString(html, 'text/html');
		function propre(n) {
			var sortie = document.createDocumentFragment();
			[].forEach.call(n.childNodes, function (c) {
				if (c.nodeType === 3) { sortie.appendChild(document.createTextNode(c.nodeValue)); return; }
				if (c.nodeType !== 1 || /^(SCRIPT|STYLE|META|LINK|TITLE|O:P)$/.test(c.nodeName)) return;
				var nom = c.nodeName === 'H1' ? 'H2' : (/^H[5-6]$/.test(c.nodeName) ? 'H4' : (c.nodeName === 'DIV' ? 'P' : c.nodeName));
				var enfants = propre(c);
				if (!GARDER[nom]) { sortie.appendChild(enfants); return; }
				var el = document.createElement(nom);
				if (nom === 'A' && /^(https?:|mailto:|tel:)/i.test(c.getAttribute('href') || '')) el.setAttribute('href', c.getAttribute('href'));
				el.appendChild(enfants);
				sortie.appendChild(el);
			});
			return sortie;
		}
		var boite = document.createElement('div');
		boite.appendChild(propre(doc.body));
		return boite.innerHTML.replace(/<p>\s*<\/p>/g, '');
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
		demander({ titre: 'Insérer un lien', texte: 'Adresse commençant par https://, mailto: (courriel) ou tel: (téléphone).', champ: 'https://', valider: 'Insérer', icone: 'lien' }).then(function (url) {
			if (url === null) return;
			url = url.trim();
			if (!/^(https?:\/\/|mailto:|tel:)/i.test(url)) return notifier('Adresse invalide : elle doit commencer par https://, mailto: ou tel:.', true);
			restaurer(zone, range);
			if (range && range.collapsed) document.execCommand('insertHTML', false, '<a href="' + esc(url) + '">' + esc(url) + '</a>');
			else document.execCommand('createLink', false, url);
		});
	}

	function commande(zone, cmd, valeur) {
		var riche = zone.closest('.g-riche');
		if (cmd === 'link') return lien(zone);
		if (cmd === 'image' || cmd === 'document') {
			garderSelection(zone);
			riche.querySelector(cmd === 'image' ? '.g-riche__fichier' : '.g-riche__document').click();
			return;
		}
		if (cmd === 'plein-ecran') {
			var plein = riche.classList.toggle('g-riche--plein');
			document.documentElement.classList.toggle('g-sans-defilement', plein);
			var b = riche.querySelector('[data-cmd="plein-ecran"]');
			b.setAttribute('aria-pressed', plein ? 'true' : 'false');
			b.innerHTML = ico(plein ? 'ecran-normal' : 'plein-ecran');
			b.title = plein ? 'Quitter le plein écran (Échap)' : 'Plein écran';
			zone.focus();
			return;
		}
		if (cmd === 'palette-couleur' || cmd === 'palette-surligner') {
			garderSelection(zone);
			var bouton = riche.querySelector('[data-cmd="' + cmd + '"]');
			var palette = bouton.nextElementSibling;
			var ouvrir = palette.hidden;
			fermerPalettes(riche);
			palette.hidden = !ouvrir;
			bouton.setAttribute('aria-expanded', ouvrir ? 'true' : 'false');
			return;
		}
		if (cmd === 'video') {
			garderSelection(zone);
			var r1 = zone._range;
			return demander({ titre: 'Insérer une vidéo', texte: 'Collez le lien de la vidéo YouTube ou Vimeo. Elle s’affichera en lecteur sur le site.', champ: 'https://', valider: 'Insérer', icone: 'video' }).then(function (url) {
				if (url === null) return;
				url = url.trim();
				if (!/^https?:\/\/(www\.|m\.)?(youtube\.com|youtu\.be|vimeo\.com)\//i.test(url)) return notifier('Lien invalide : il doit venir de YouTube ou de Vimeo.', true);
				restaurer(zone, r1);
				/* Seul sur sa ligne, le lien devient un lecteur vidéo à l'affichage (intégration de WordPress). */
				document.execCommand('insertHTML', false, '<p class="g-video">' + esc(url) + '</p><p><br></p>');
				compter(zone);
			});
		}
		if (cmd === 'table') {
			garderSelection(zone);
			var r2 = zone._range;
			return demander({ titre: 'Insérer un tableau', texte: 'Nombre de colonnes et de lignes, par exemple « 3 x 4 ». La première ligne sert d’en-tête.', champ: '3 x 4', valider: 'Insérer', icone: 'tableau2' }).then(function (v) {
				if (v === null) return;
				var m = String(v).match(/(\d+)\D+(\d+)/);
				var col = Math.min(8, Math.max(1, m ? parseInt(m[1], 10) : 3));
				var lig = Math.min(30, Math.max(1, m ? parseInt(m[2], 10) : 4));
				var cellules = function (balise) { return new Array(col + 1).join('<' + balise + '><br></' + balise + '>'); };
				var html = '<table><thead><tr>' + cellules('th') + '</tr></thead><tbody>' + new Array(lig).join('<tr>' + cellules('td') + '</tr>') + '</tbody></table><p><br></p>';
				restaurer(zone, r2);
				document.execCommand('insertHTML', false, html);
			});
		}
		if (cmd === 'guillemets') {
			zone.focus();
			var sel = window.getSelection();
			var texte = sel.rangeCount ? sel.toString() : '';
			document.execCommand('insertText', false, '«\u00a0' + texte + '\u00a0»');
			return;
		}
		zone.focus();
		if (cmd === 'foreColor' || cmd === 'hiliteColor') {
			restaurer(zone, zone._range);
			fermerPalettes(riche);
			/* En CSS (span style) plutôt qu'en balise <font>, que le site n'accepte pas. */
			document.execCommand('styleWithCSS', false, true);
			document.execCommand(cmd, false, valeur);
			document.execCommand('styleWithCSS', false, false);
		} else if (cmd === 'bloc') {
			document.execCommand('formatBlock', false, '<' + valeur + '>');
		} else {
			document.execCommand(cmd, false, null);
		}
		majOutils();
		compter(zone);
	}

	function fermerPalettes(dans) {
		[].forEach.call((dans || document).querySelectorAll('.g-riche__palette'), function (p) {
			p.hidden = true;
			var b = p.previousElementSibling;
			if (b) b.setAttribute('aria-expanded', 'false');
		});
	}

	/* Un document (PDF, Word, Excel…) : envoyé dans la médiathèque, puis inséré comme lien de téléchargement. */
	function insererDocument(input) {
		var zone = input.closest('.g-riche').querySelector('.g-riche__zone');
		var fichier = input.files[0];
		input.value = '';
		if (!fichier) return;
		var fd = new FormData();
		fd.append('file', fichier, fichier.name);
		notifier('Envoi du document…');
		api('wp/v2/media', { method: 'POST', body: fd }).then(function (res) {
			var nom = fichier.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
			var ext = (fichier.name.match(/\.([^.]+)$/) || ['', ''])[1].toUpperCase();
			restaurer(zone, zone._range);
			document.execCommand('insertHTML', false, '<p><a href="' + esc(res.data.source_url) + '">Télécharger : ' + esc(nom) + (ext ? ' (' + esc(ext) + ')' : '') + '</a></p>');
			notifier('Document joint.');
		}).catch(function (err) {
			notifier('Envoi impossible : ' + err.message, true);
		});
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
			return demander({ titre: 'Décrire l’image', texte: 'Ce que montre l’image, lu aux personnes malvoyantes (ex. « Remise des diplômes au campus d’Ebolowa »).', champ: '', valider: 'Insérer', icone: 'image' }).then(function (alt) {
				restaurer(zone, zone._range);
				document.execCommand('insertHTML', false, '<img src="' + esc(src) + '" alt="' + esc(alt || '') + '">');
				notifier('Image insérée.');
			});
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
		[].forEach.call(zone.parentNode.querySelectorAll('[data-etat]'), function (b) {
			if (b.getAttribute('data-etat') === 'plein-ecran') return;
			var actif = false;
			try { actif = document.queryCommandState(b.getAttribute('data-etat')); } catch (e) { /* sans effet */ }
			b.setAttribute('aria-pressed', actif ? 'true' : 'false');
		});
		var choix = zone.parentNode.querySelector('[data-bloc-choix]');
		if (choix) choix.value = BLOCS.some(function (b) { return b[0] === bloc; }) ? bloc : 'p';
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

	/** Toutes les pages d'une liste de l'API (par lots de 100) : la première donne le nombre de pages, les autres partent ensemble. */
	function toutCharger(chemin) {
		function page(n) {
			return api(chemin + (chemin.indexOf('?') > -1 ? '&' : '?') + 'per_page=100&page=' + n);
		}
		return page(1).then(function (res) {
			var suite = [];
			for (var n = 2; n <= res.pages; n++) suite.push(page(n));
			return Promise.all(suite).then(function (lots) {
				return lots.reduce(function (tous, r) { return tous.concat(r.data); }, res.data);
			});
		});
	}

	function notifier(message, erreur) {
		var t = document.getElementById('g-toast');
		if (!t) return;
		t.innerHTML = ico(erreur ? 'info' : 'droite') + '<span>' + esc(message) + '</span>';
		t.className = 'g-toast est-visible' + (erreur ? ' g-toast--erreur' : '');
		clearTimeout(notifier.t);
		notifier.t = setTimeout(function () { t.className = 'g-toast'; }, erreur ? 6000 : 3000);
	}

	/**
	 * Fenêtre de confirmation ou de saisie (remplace confirm() et prompt()).
	 * o = { titre, texte, valider, ton: 'danger' | '', champ: valeur initiale (saisie) , icone }.
	 * Promesse : true / false pour une confirmation ; le texte saisi ou null pour une saisie.
	 */
	function demander(o) {
		var d = document.getElementById('g-fenetre');
		var saisie = o.champ != null;
		if (!d || typeof d.showModal !== 'function') {
			return Promise.resolve(saisie ? window.prompt(o.titre, o.champ) : window.confirm(o.titre + (o.texte ? '\n\n' + o.texte : '')));
		}
		return new Promise(function (resoudre) {
			var danger = o.ton === 'danger';
			d.setAttribute('data-ton', danger ? 'danger' : '');
			d.innerHTML = '<form class="g-fenetre__contenu" novalidate>' +
				'<span class="g-fenetre__sceau">' + ico(o.icone || (danger ? 'poubelle' : 'info')) + '</span>' +
				'<h2 class="g-fenetre__titre" id="g-fenetre-titre">' + esc(o.titre) + '</h2>' +
				(o.texte ? '<p class="g-fenetre__texte" id="g-fenetre-texte">' + esc(o.texte) + '</p>' : '') +
				(saisie ? '<input class="g-fenetre__champ" type="text" aria-labelledby="g-fenetre-titre"' + (o.texte ? ' aria-describedby="g-fenetre-texte"' : '') + ' value="' + esc(o.champ) + '">' : '') +
				'<div class="g-fenetre__actions"><button type="button" class="g-btn" data-non>Annuler</button>' +
				'<button type="submit" class="g-btn ' + (danger ? 'g-btn--danger' : 'g-btn--primaire') + '">' + esc(o.valider || 'Confirmer') + '</button></div></form>';
			var champ = d.querySelector('.g-fenetre__champ');
			var fini = false;
			function fin(v) {
				if (fini) return;
				fini = true;
				if (d.open) d.close();
				resoudre(v);
			}
			d.querySelector('[data-non]').onclick = function () { fin(saisie ? null : false); };
			d.querySelector('form').onsubmit = function (e) { e.preventDefault(); fin(saisie ? champ.value : true); };
			d.oncancel = function () { fin(saisie ? null : false); };
			d.onclick = function (e) { if (e.target === d) fin(saisie ? null : false); }; // clic sur le fond
			d.showModal();
			/* Pour une action destructrice, le focus va d'abord sur « Annuler ». */
			if (champ) { champ.focus(); champ.select(); } else (danger ? d.querySelector('[data-non]') : d.querySelector('[type=submit]')).focus();
		});
	}

	function lire(p, chemin) {
		if (chemin.indexOf('meta.') === 0) return (p.meta || {})[chemin.slice(5)];
		var v = p[chemin];
		return v && typeof v === 'object' && !Array.isArray(v) && 'raw' in v ? v.raw : v;
	}

	function trouver(id) {
		return RUBRIQUES.filter(function (r) { return r.id === id; })[0];
	}

	function titreDe(p) {
		return (p.title && (p.title.raw || p.title.rendered)) || '(sans titre)';
	}

	/* ---------- Arborescence des structures (établissement > département > filière > UE) ---------- */

	function chargerArbre() {
		if (arbre) return Promise.resolve(arbre);
		/* Sans droit sur les structures (ex. personnel seul), l'arbre public suffit aux listes de choix. */
		var edition = a('sueb_etablissements') || a('sueb_formations');
		return toutCharger('wp/v2/ueb_structure?' + (edition ? 'context=edit&status=any&' : '') + 'orderby=menu_order&order=asc&_fields=id,title,parent,menu_order,status,meta._sueb_sigle').then(function (tous) {
			var a = { par: {}, enfants: {}, ordre: [] };
			tous.forEach(function (p) {
				a.par[p.id] = { id: p.id, parent: p.parent || 0, ordre: p.menu_order || 0, nom: titreDe(p), sigle: m(p, 'sigle') };
			});
			/* Un compte qui gère les formations sans la page de l'établissement ne reçoit pas ce dernier de l'API
			   (il ne peut pas le modifier) : on le replace à la racine, pour y rattacher ses départements. */
			(C.etablissements || []).forEach(function (e, i) {
				if (!a.par[e.id]) a.par[e.id] = { id: e.id, parent: 0, ordre: i, nom: e.nom, sigle: e.sigle || '' };
			});
			Object.keys(a.par).forEach(function (id) {
				var n = a.par[id];
				var parent = a.par[n.parent] ? n.parent : 0;
				(a.enfants[parent] = a.enfants[parent] || []).push(n);
			});
			Object.keys(a.enfants).forEach(function (k) {
				a.enfants[k].sort(function (x, y) { return x.ordre - y.ordre || x.nom.localeCompare(y.nom, 'fr'); });
			});
			(function parcourir(parent, prof) {
				(a.enfants[parent] || []).forEach(function (n) {
					n.prof = prof;
					a.ordre.push(n);
					parcourir(n.id, prof + 1);
				});
			})(0, 0);
			arbre = a;
			return a;
		});
	}

	/** Vrai si `id` est `ancetre` lui-même ou l'un de ses descendants. */
	function descendDe(id, ancetre) {
		for (var n = arbre.par[id]; n; n = arbre.par[n.parent]) {
			if (n.id === ancetre) return true;
		}
		return false;
	}

	function optionsStructures(f, v, idCourant) {
		var choisi = String(v || '');
		var opt = function (n, libelle) {
			return '<option value="' + n.id + '"' + (String(n.id) === choisi ? ' selected' : '') + '>' + esc(libelle) + '</option>';
		};
		/* Les établissements de la portée du compte (C.etablissements) ; le rectorat pour une portée « tous » seulement. */
		var etabs = (arbre.enfants[0] || []).filter(function (e) { return TOUS || (C.etablissements || []).some(function (x) { return x.id === e.id; }); });
		if (f.type === 'etablissement') {
			return (TOUS ? '<option value="">— Rectorat / aucun —</option>' : '') + etabs.map(function (e) {
				return opt(e, (e.sigle ? e.sigle + ' — ' : '') + e.nom);
			}).join('');
		}
		if (f.type === 'departement') {
			return '<option value="">— Aucun —</option>' + etabs.map(function (e) {
				var deps = arbre.enfants[e.id] || [];
				return deps.length ? '<optgroup label="' + esc(e.sigle || e.nom) + '">' + deps.map(function (d) { return opt(d, d.nom); }).join('') + '</optgroup>' : '';
			}).join('');
		}
		/* Rattachement : une UE (niveau 3) ne peut rien accueillir ; une structure ne se rattache pas à elle-même ni à ses descendants. */
		return '<option value="">— Choisir —</option>' + arbre.ordre.filter(function (n) {
			return n.prof < 3 && !(idCourant && descendDe(n.id, idCourant));
		}).map(function (n) {
			return opt(n, new Array(n.prof + 1).join('— ') + n.nom + ' (' + NIVEAUX[n.prof] + ')');
		}).join('');
	}

	/* Formations : ce qu'on ajoute (1 département, 2 filière, 3 UE) décide de la liste de rattachement,
	   limitée au niveau juste au-dessus et rangée par établissement (puis par département pour une UE). */
	var GENRES = { 1: ['Un département', 'Établissement'], 2: ['Une filière', 'Département'], 3: ['Une unité d’enseignement (UE)', 'Filière'] };

	function optionsRattachement(genre, v, idCourant) {
		var choisi = String(v || '');
		var opt = function (n) {
			return '<option value="' + n.id + '"' + (String(n.id) === choisi ? ' selected' : '') + '>' + esc(n.nom) + '</option>';
		};
		var libre = function (n) { return !(idCourant && descendDe(n.id, idCourant)); };
		var etabs = (arbre.enfants[0] || []).filter(function (e) { return TOUS || (C.etablissements || []).some(function (x) { return x.id === e.id; }); });
		var vide = '<option value="">— Choisir —</option>';
		if (genre === 1) return vide + etabs.filter(libre).map(opt).join('');
		var groupe = function (titre, noeuds) {
			noeuds = noeuds.filter(libre);
			return noeuds.length ? '<optgroup label="' + esc(titre) + '">' + noeuds.map(opt).join('') + '</optgroup>' : '';
		};
		return vide + etabs.map(function (e) {
			var deps = arbre.enfants[e.id] || [];
			if (genre === 2) return groupe(e.sigle || e.nom, deps);
			return deps.map(function (d) { return groupe((e.sigle || e.nom) + ' › ' + d.nom, arbre.enfants[d.id] || []); }).join('');
		}).join('');
	}

	/* Parcours d'un niveau qui le dit lui-même (BTS 1, Niveau 3, L3 pro, A2) ; '' pour L1, M2 et les autres. */
	function parcoursDuNiveau(n) {
		if (/bts|^niveau\s*\d/i.test(n)) return 'bts-ingenierie';
		if (/\bpro\b|^[LM]P\s?\d/i.test(n)) return 'pro';
		if (/^A\s?\d$/i.test(n)) return 'medecine';
		return /^[LM]\s?\d$/i.test(n) ? 'classique' : '';
	}

	/* Les cases « Niveaux ouverts » d'une filière suivent le parcours choisi. Les niveaux déjà enregistrés
	   qui n'en font pas partie (BTS d'une filière qui va jusqu'à la licence professionnelle, « 1er cycle »…)
	   restent proposés à part, cochés : rien ne se perd à l'enregistrement. */
	function majNiveauxOuverts(form, initial) {
		var zone = form.querySelector('.g-niveaux-ouverts');
		var sel = form.querySelector('[data-path="meta._sueb_parcours"]');
		if (!zone) return;
		var gardes = zone.getAttribute('data-enregistres').split(',').map(function (x) { return x.trim(); }).filter(Boolean);
		if (sel && initial && !sel.value) {
			/* Filière sans parcours enregistré : celui de son premier niveau reconnu. */
			sel.value = gardes.map(parcoursDuNiveau).filter(Boolean)[0] || '';
		}
		var p = (C.parcours || []).filter(function (x) { return sel && x.cle === sel.value; })[0];
		var coches = initial ? gardes : [].map.call(zone.querySelectorAll('input:checked'), function (c) { return c.value; }).filter(function (n) { return gardes.indexOf(n) > -1; });
		var cases = function (liste) {
			return liste.map(function (n) {
				return '<label><input type="checkbox" value="' + esc(n) + '"' + (coches.indexOf(n) > -1 ? ' checked' : '') + '> ' + esc(n) + '</label>';
			}).join('');
		};
		var autres = gardes.filter(function (n) { return !p || p.niveaux.indexOf(n) === -1; });
		zone.innerHTML = (p ? '<div class="g-niveaux-ouverts__liste">' + cases(p.niveaux) + '</div>' : '<p class="g-aide">Choisissez d’abord le parcours : ses niveaux s’afficheront ici.</p>') +
			(autres.length ? '<p class="g-aide">Autres niveaux déjà enregistrés :</p><div class="g-niveaux-ouverts__liste">' + cases(autres) + '</div>' : '');
	}

	/* ---------- Coque : barre, menu, zone de contenu ---------- */

	function initiales(nom) {
		return String(nom || '?').trim().split(/\s+/).slice(0, 2).map(function (m) { return m.charAt(0); }).join('').toUpperCase();
	}

	function monter() {
		var menu = GROUPES.map(function (g, i) {
			var items = RUBRIQUES.filter(function (r) { return r.groupe === i; }).map(function (r) {
				return '<li><a href="#' + r.id + '" data-menu="' + r.id + '">' + ico(icone(r)) + '<span>' + esc(r.titre) + '</span></a></li>';
			}).join('');
			return items ? '<p class="g-nav__groupe">' + esc(g) + '</p><ul>' + items + '</ul>' : '';
		}).join('');
		app.innerHTML =
			'<h1 class="sr">Gestion du site de l’Université d’Ebolowa</h1>' +
			'<aside class="g-nav" id="g-nav" aria-label="Rubriques de la gestion">' +
				'<a class="g-nav__marque" href="#"><img src="' + esc(C.logo) + '" alt="" width="40" height="40"><span><strong>UEb</strong><small>Gestion du site</small></span></a>' +
				'<nav class="g-nav__liens"><ul><li><a href="#" data-menu="">' + ico('tableau') + '<span>Tableau de bord</span></a></li></ul>' + menu + '</nav>' +
				'<div class="g-nav__pied">' +
					'<div class="g-compte"><span class="g-compte__avatar" aria-hidden="true">' + esc(initiales(C.nom)) + '</span><span class="g-compte__meta"><strong>' + esc(C.nom) + '</strong><small>' + esc(C.role || '') + '</small></span></div>' +
					'<a class="g-nav__bas" href="' + esc(C.site) + '" target="_blank" rel="noopener">' + ico('externe') + 'Voir le site</a>' +
					'<a class="g-nav__bas" href="' + esc(C.deconnexion) + '">' + ico('sortie') + 'Se déconnecter</a>' +
				'</div>' +
			'</aside>' +
			'<div class="g-voile" data-fermer-nav></div>' +
			'<div class="g-principal">' +
				'<header class="g-mobile"><button type="button" class="g-mobile__menu" aria-controls="g-nav" aria-expanded="false" data-ouvrir-nav>' + ico('menu') + '<span class="sr">Ouvrir le menu</span></button>' +
				'<img src="' + esc(C.logo) + '" alt="" width="30" height="30"><span>Gestion du site</span></header>' +
				/* Barre du haut, comme l'administration des inscriptions : où l'on est (groupe › rubrique), et l'accès au site. */
				'<header class="g-barre"><p class="g-barre__lieu" id="g-lieu"></p>' +
				'<div class="g-barre__actions"><a class="g-btn g-btn--petit" href="' + esc(C.site) + '" target="_blank" rel="noopener">' + ico('externe') + 'Voir le site</a>' +
				'<span class="g-barre__compte" title="' + esc(C.nom) + '"><span class="g-compte__avatar" aria-hidden="true">' + esc(initiales(C.nom)) + '</span><span class="g-barre__nom">' + esc(C.nom) + '</span></span></div></header>' +
				'<main class="g-contenu" id="g-contenu" tabindex="-1"></main>' +
			'</div>' +
			'<div class="g-toast" id="g-toast" role="status" aria-live="polite"></div>' +
			'<dialog class="g-fenetre" id="g-fenetre" aria-labelledby="g-fenetre-titre"></dialog>';
		contenu = document.getElementById('g-contenu');

		/* Menu latéral en tiroir sur petit écran. */
		var bouton = app.querySelector('[data-ouvrir-nav]');
		function navigation(ouvrir) {
			app.classList.toggle('nav-ouverte', ouvrir);
			bouton.setAttribute('aria-expanded', ouvrir ? 'true' : 'false');
			if (ouvrir) app.querySelector('.g-nav__liens a').focus();
		}
		bouton.addEventListener('click', function () { navigation(true); });
		app.addEventListener('click', function (e) {
			if (e.target.closest('[data-fermer-nav]') || (app.classList.contains('nav-ouverte') && e.target.closest('.g-nav a[href^="#"]'))) navigation(false);
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && app.classList.contains('nav-ouverte')) { navigation(false); bouton.focus(); }
		});
	}

	function majMenu(id) {
		[].forEach.call(app.querySelectorAll('[data-menu]'), function (a) {
			if (a.getAttribute('data-menu') === id) a.setAttribute('aria-current', 'page');
			else a.removeAttribute('aria-current');
		});
		/* La barre du haut : « Groupe › Rubrique », ou le tableau de bord. */
		var lieu = document.getElementById('g-lieu');
		if (!lieu) return;
		var r = id ? trouver(id) : null;
		lieu.innerHTML = '<span class="g-barre__ico">' + ico(r ? icone(r) : 'tableau') + '</span>' +
			(r ? '<span class="g-barre__groupe">' + esc(GROUPES[r.groupe] || '') + '</span>' + ico('droite', 'g-barre__sep') + '<b>' + esc(r.titre) + '</b>' : '<b>Tableau de bord</b>');
	}

	/* ---------- Routeur ---------- */

	function route() {
		var h = location.hash.replace(/^#/, '').split('/');
		var r = trouver(h[0]);
		contenu.onclick = null; // le gestionnaire de clics posé par l’écran « Rôles et comptes »
		majMenu(r && !r.bientot ? r.id : '');
		window.scrollTo(0, 0);
		if (!r || r.bientot) return tableauDeBord();
		/* Recherche, filtre et page repartent de zéro quand on change de rubrique. */
		if (etat.rubrique !== r.id) etat = { page: 1, recherche: '', statut: 'any', rubrique: r.id };
		if (r.reglages) return reglages(r);
		if (r.carte) return carte(r);
		if (r.contacts) return contactsSites(r);
		if (r.comptes) return comptes(r, h.slice(1));
		if (r.mediatheque && (!h[1] || h[1] === 'new')) return mediatheque(r);
		if (h[1] === 'new') return formulaire(r, 0);
		if (h[1]) return formulaire(r, parseInt(h[1], 10));
		liste(r);
	}

	/* Chiffres clés du tableau de bord : le total de chaque requête (en-tête X-WP-Total). */
	var CHIFFRES = [
		{ t: 'Actualités en ligne', i: 'journal', q: 'wp/v2/posts?status=publish', lien: 'actualites', pied: 'articles publiés' },
		{ t: 'Brouillons', i: 'crayon', q: 'wp/v2/posts?status=draft', lien: 'actualites', pied: 'actualités à terminer', ton: 'or' },
		{ t: 'Évènements', i: 'agenda', q: 'wp/v2/ueb_evenement?status=publish', lien: 'evenements', pied: 'dans l’agenda' },
		{ t: 'Personnel', i: 'personnes', q: 'wp/v2/ueb_personnel?status=publish', lien: 'personnel', pied: 'fiches de l’annuaire' },
		{ t: 'Offre de formation', i: 'diplome', q: 'wp/v2/ueb_structure?status=publish', lien: 'formations', pied: 'établissements, filières, UE' },
		{ t: 'Médiathèque', i: 'image', q: 'wp/v2/media?status=inherit' + (a('sueb_mediatheque') ? '' : '&author=' + C.id), lien: 'mediatheque', pied: 'fichiers envoyés', ton: 'ciel' }
	];

	function tableauDeBord() {
		var heure = new Date().getHours();
		var prenom = String(C.nom || '').trim().split(/\s+/)[0];
		var aujourdhui = new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
		CHIFFRES = CHIFFRES.filter(function (k) { return trouver(k.lien); });
		var actus = !!trouver('actualites');
		var chiffres = CHIFFRES.map(function (k, n) {
			return '<li class="g-kpi' + (k.ton ? ' g-kpi--' + k.ton : '') + '" style="--n:' + n + '"><a href="#' + k.lien + '">' +
				'<span class="g-kpi__tete"><span class="g-kpi__libelle">' + esc(k.t) + '</span><span class="g-kpi__ico">' + ico(k.i) + '</span></span>' +
				'<strong class="g-kpi__valeur" data-kpi="' + n + '"><span class="g-squelette"></span></strong><span class="g-kpi__pied">' + esc(k.pied) + '</span></a></li>';
		}).join('');
		var raccourcis = RUBRIQUES.map(function (r) {
			return '<li><a href="#' + r.id + '"><span class="g-raccourci__ico">' + ico(icone(r)) + '</span><span><strong>' + esc(r.titre) + '</strong><small>' + esc(description(r)) + '</small></span></a></li>';
		}).join('');
		contenu.innerHTML = entete((heure < 18 ? 'Bonjour' : 'Bonsoir') + (prenom ? ', ' + prenom : ''), aujourdhui.charAt(0).toUpperCase() + aujourdhui.slice(1) + ' · vue d’ensemble du site',
				'<a class="g-btn" href="' + esc(C.site) + '" target="_blank" rel="noopener">' + ico('externe') + 'Voir le site</a>' + (actus ? '<a class="g-btn g-btn--primaire" href="#actualites/new">' + ico('plus') + 'Nouvelle actualité</a>' : '')) +
			'<ul class="g-kpis">' + chiffres + '</ul>' +
			'<div class="g-deux">' +
				(actus ? '<section class="g-panneau" aria-labelledby="g-recents-titre"><div class="g-panneau__tete"><h3 id="g-recents-titre">Dernières actualités modifiées</h3><a class="g-lien-fleche" href="#actualites">Tout voir' + ico('droite') + '</a></div><div id="g-recents"><p class="g-chargement"><span class="g-roue"></span>Chargement…</p></div></section>' : '') +
				'<section class="g-panneau" aria-labelledby="g-acces-titre"><div class="g-panneau__tete"><h3 id="g-acces-titre">Toutes les rubriques</h3></div><ul class="g-raccourcis">' + raccourcis + '</ul></section>' +
			'</div>';

		CHIFFRES.forEach(function (k, n) {
			api(k.q + '&per_page=1&_fields=id&context=edit').then(function (res) {
				var el = contenu.querySelector('[data-kpi="' + n + '"]');
				if (el) el.textContent = res.total.toLocaleString('fr-FR');
			}).catch(function () {
				var el = contenu.querySelector('[data-kpi="' + n + '"]');
				if (el) el.textContent = '—';
			});
		});

		if (actus) api('wp/v2/posts?context=edit&status=publish,draft,future,pending,private&per_page=6&orderby=modified&order=desc&_fields=id,title,status,modified').then(function (res) {
			var zone = document.getElementById('g-recents');
			if (!zone) return;
			zone.innerHTML = res.data.length ? '<ul class="g-recents">' + res.data.map(function (p) {
				return '<li><a href="#actualites/' + p.id + '"><span class="g-recents__titre">' + esc(titreDe(p)) + '</span><small>Modifiée le ' + esc(dateFr(p.modified, true)) + '</small></a>' +
					'<span class="g-etat g-etat--' + esc(p.status) + '">' + esc(STATUTS[p.status] || p.status) + '</span></li>';
			}).join('') + '</ul>' : '<p class="g-vide">Aucune actualité pour l’instant. <a href="#actualites/new">Rédiger la première</a></p>';
		}).catch(function (e) {
			var zone = document.getElementById('g-recents');
			if (zone) zone.innerHTML = '<p class="g-erreur" role="alert">' + esc(e.message) + '</p>';
		});
	}

	/* ---------- Liste ---------- */

	function liste(r) {
		var statuts = [['any', 'Tous'], ['publish', 'Publiés'], ['draft', 'Brouillons'], ['trash', 'Corbeille']].map(function (s) {
			return '<option value="' + s[0] + '"' + (etat.statut === s[0] ? ' selected' : '') + '>' + s[1] + '</option>';
		}).join('');
		contenu.innerHTML =
			entete(r.titre, description(r), r.id === 'etablissements' && !TOUS ? '' : '<a class="g-btn g-btn--primaire" href="#' + r.id + '/new">' + ico('plus') + 'Ajouter</a>') +
			'<div class="g-outils"><div class="g-recherche">' + ico('recherche') + '<label class="sr" for="g-q">Rechercher</label><input type="search" id="g-q" placeholder="Rechercher par titre…" value="' + esc(etat.recherche) + '"></div>' +
			'<label class="sr" for="g-st">Statut</label><select id="g-st">' + statuts + '</select></div><div id="g-liste" aria-live="polite"><p class="g-chargement"><span class="g-roue"></span>Chargement…</p></div>';

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
		var tri = r.ordre === 'menu_order' ? '&orderby=menu_order&order=asc' : '&orderby=date&order=desc';
		var q = 'wp/v2/' + r.endpoint + '?context=edit&per_page=15&page=' + etat.page + '&status=' + etat.statut + tri + (r.filtre || '') + '&_fields=id,title,date,status,format,sticky,link,parent,menu_order,meta' + (r.pages ? ',sueb_gestion' : '');
		if (etat.recherche) q += '&search=' + encodeURIComponent(etat.recherche);
		var colonnes = (r.colonnes || []).concat([{ t: 'Statut', statut: true }]);
		Promise.all([api(q), r.arbre ? chargerArbre() : null]).then(function (res) {
			res = res[0];
			if (!res.data.length) {
				zone.innerHTML = '<p class="g-vide">Aucun contenu' + (etat.recherche ? ' ne correspond à cette recherche' : '') + '.</p>';
				return;
			}
			var corbeille = etat.statut === 'trash';
			var lignes = res.data.map(function (p) {
				var titre = titreDe(p);
				/* Une page dont le thème a besoin ne se supprime pas. */
				var protegee = p.sueb_gestion && p.sueb_gestion.role;
				var actions = corbeille
					? '<button type="button" data-act="restaurer" data-id="' + p.id + '">Restaurer</button><button type="button" class="g-danger" data-act="supprimer" data-id="' + p.id + '" data-titre="' + esc(titre) + '">Supprimer définitivement</button>'
					: '<a href="#' + r.id + '/' + p.id + '">Modifier</a>' + (p.status === 'publish' && p.link ? '<a href="' + esc(p.link) + '" target="_blank" rel="noopener">Voir</a>' : '') +
					  (protegee ? '' : '<button type="button" class="g-danger" data-act="corbeille" data-id="' + p.id + '" data-titre="' + esc(titre) + '">Supprimer</button>');
				var cellules = colonnes.map(function (c) {
					return c.statut
						? '<td><span class="g-etat g-etat--' + esc(p.status) + '">' + esc(STATUTS[p.status] || p.status) + '</span></td>'
						: '<td' + (c.court ? ' class="g-court"' : '') + '>' + esc(c.v(p)) + '</td>';
				}).join('');
				return '<tr><th scope="row">' + esc(titre) + '</th>' + cellules + '<td class="g-actions">' + actions + '</td></tr>';
			}).join('');
			var entete = '<th scope="col">Titre</th>' + colonnes.map(function (c) { return '<th scope="col">' + esc(c.t) + '</th>'; }).join('') + '<th scope="col"><span class="sr">Actions</span></th>';
			var pagination = res.pages > 1
				? '<nav class="g-pagination" aria-label="Pages"><button type="button" data-page="-1"' + (etat.page <= 1 ? ' disabled' : '') + '>Précédent</button><span>Page ' + etat.page + ' sur ' + res.pages + '</span><button type="button" data-page="1"' + (etat.page >= res.pages ? ' disabled' : '') + '>Suivant</button></nav>'
				: '';
			zone.innerHTML = '<div class="g-tableau"><table><thead><tr>' + entete + '</tr></thead><tbody>' + lignes + '</tbody></table></div>' + pagination + '<p class="g-total">' + res.total + ' élément' + (res.total > 1 ? 's' : '') + '</p>';
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
		var enfants = r.arbre && arbre && arbre.enfants[id] ? arbre.enfants[id].length : 0;
		var avert = enfants ? ' Attention : ' + enfants + ' structure' + (enfants > 1 ? 's y sont rattachées' : ' y est rattachée') + ' et ne s’afficheront plus correctement.' : '';
		var question = {
			corbeille: { titre: 'Mettre à la corbeille ?', texte: '« ' + titre + ' » ne sera plus visible sur le site. Vous pourrez le restaurer depuis la corbeille.' + avert, valider: 'Mettre à la corbeille', ton: 'danger' },
			supprimer: { titre: 'Supprimer définitivement ?', texte: '« ' + titre + ' » sera effacé pour de bon. Cette action est irréversible.' + avert, valider: 'Supprimer', ton: 'danger' }
		}[act];
		(question ? demander(question) : Promise.resolve(true)).then(function (ok) {
			if (!ok) return;
			var tache;
			if (act === 'corbeille') {
				tache = api(base, { method: 'DELETE' }).then(function () { notifier('Mis à la corbeille.'); });
			} else if (act === 'restaurer') {
				tache = api(base, { method: 'POST', body: { status: 'draft' } }).then(function () { notifier('Restauré en brouillon.'); });
			} else if (act === 'supprimer') {
				tache = api(base + '?force=true', { method: 'DELETE' }).then(function () { notifier('Supprimé définitivement.'); });
			}
			if (tache) tache.then(function () { if (r.arbre) arbre = null; charger(r); }).catch(function (e) { notifier(e.message, true); });
		});
	}

	/* ---------- Réglages du site (route sueb/v1/reglages) ---------- */

	function reglages(r) {
		contenu.innerHTML = '<p class="g-chargement"><span class="g-roue"></span>Chargement…</p>';
		var champs = ((C.reglages || {})[r.reglages] || []).map(function (c) {
			var f = { path: c.cle, label: c.label, type: c.type, aide: c.aide };
			if (c.type === 'video') { f.type = 'fichier'; f.accept = 'video/*'; }
			if (c.cle === 'frise') f.lignes = 7;
			return f;
		});
		api('sueb/v1/reglages').then(function (res) {
			dessiner(Object.assign({}, r, { champs: champs }), 0, res.data);
		}).catch(function (e) {
			contenu.innerHTML = '<p class="g-erreur" role="alert">' + esc(e.message) + '</p>';
		});
	}

	/* ---------- Médiathèque ---------- */

	var TYPES_MEDIA = [['', 'Tous les fichiers'], ['image', 'Images'], ['video', 'Vidéos'], ['audio', 'Audio'], ['application', 'Documents']];

	function extension(url) {
		return (String(url).split('?')[0].split('.').pop() || 'fichier').toUpperCase();
	}

	function vignette(m, taille) {
		var t = (m.media_details && m.media_details.sizes) || {};
		return ((t[taille] || t.medium || t.thumbnail || {}).source_url) || m.source_url;
	}

	function copier(texte) {
		if (!navigator.clipboard) return notifier('Copie impossible dans ce navigateur : sélectionnez l’adresse.', true);
		navigator.clipboard.writeText(texte).then(function () { notifier('Adresse copiée.'); }, function () { notifier('Copie impossible : sélectionnez l’adresse.', true); });
	}

	function mediatheque(r) {
		var types = TYPES_MEDIA.map(function (t) {
			return '<option value="' + t[0] + '"' + ((etat.type || '') === t[0] ? ' selected' : '') + '>' + t[1] + '</option>';
		}).join('');
		contenu.innerHTML =
			entete(r.titre, description(r), '<label class="g-btn g-btn--primaire g-envoi">' + ico('envoi') + 'Envoyer des fichiers<input type="file" id="g-envoi" multiple></label>') +
			'<div class="g-outils"><div class="g-recherche">' + ico('recherche') + '<label class="sr" for="g-q">Rechercher</label><input type="search" id="g-q" placeholder="Rechercher un fichier…" value="' + esc(etat.recherche) + '"></div>' +
			'<label class="sr" for="g-ty">Type de fichier</label><select id="g-ty">' + types + '</select></div><div id="g-liste" aria-live="polite"><p class="g-chargement"><span class="g-roue"></span>Chargement…</p></div>';

		var minuteur;
		document.getElementById('g-q').addEventListener('input', function (e) {
			clearTimeout(minuteur);
			var v = e.target.value;
			minuteur = setTimeout(function () { etat.recherche = v; etat.page = 1; chargerMedias(); }, 350);
		});
		document.getElementById('g-ty').addEventListener('change', function (e) {
			etat.type = e.target.value;
			etat.page = 1;
			chargerMedias();
		});
		document.getElementById('g-envoi').addEventListener('change', function (e) {
			var fichiers = [].slice.call(e.target.files);
			e.target.value = '';
			envoyerPlusieurs(fichiers);
		});
		document.getElementById('g-liste').addEventListener('click', function (e) {
			var cp = e.target.closest('[data-copier]');
			if (cp) return copier(cp.getAttribute('data-copier'));
			var b = e.target.closest('[data-suppr]');
			if (!b) return;
			demander({ titre: 'Supprimer ce fichier ?', texte: '« ' + b.getAttribute('data-titre') + ' » sera effacé pour de bon et disparaîtra des contenus qui l’utilisent.', valider: 'Supprimer', ton: 'danger' }).then(function (ok) {
				if (!ok) return;
				api('wp/v2/media/' + b.getAttribute('data-suppr') + '?force=true', { method: 'DELETE' }).then(function () {
					notifier('Fichier supprimé.');
					chargerMedias();
				}).catch(function (err) { notifier(err.message, true); });
			});
		});
		chargerMedias();
	}

	/* Envoie les fichiers un par un, pour suivre l'avancement et ne pas saturer le serveur. */
	function envoyerPlusieurs(fichiers) {
		var echecs = [];
		var suite = fichiers.reduce(function (promesse, f, i) {
			return promesse.then(function () {
				notifier('Envoi ' + (i + 1) + ' sur ' + fichiers.length + ' : ' + f.name + '…');
				var fd = new FormData();
				fd.append('file', f, f.name);
				return api('wp/v2/media', { method: 'POST', body: fd }).catch(function (err) { echecs.push(f.name + ' (' + err.message + ')'); });
			});
		}, Promise.resolve());
		suite.then(function () {
			var reussis = fichiers.length - echecs.length;
			if (echecs.length) notifier(reussis + ' fichier(s) envoyé(s). Échec : ' + echecs.join(', '), true);
			else notifier(reussis > 1 ? reussis + ' fichiers envoyés.' : 'Fichier envoyé.');
			etat.page = 1;
			chargerMedias();
		});
	}

	function chargerMedias() {
		var zone = document.getElementById('g-liste');
		if (!zone) return;
		var q = 'wp/v2/media?context=edit&per_page=24&page=' + etat.page + '&orderby=date&order=desc&_fields=id,title,source_url,mime_type,media_details,date';
		if (!a('sueb_mediatheque')) q += '&author=' + C.id; // sans « Toute la médiathèque » : ses propres fichiers
		if (etat.type) q += '&media_type=' + etat.type;
		if (etat.recherche) q += '&search=' + encodeURIComponent(etat.recherche);
		api(q).then(function (res) {
			if (!res.data.length) {
				zone.innerHTML = '<p class="g-vide">Aucun fichier' + (etat.recherche || etat.type ? ' ne correspond à cette recherche' : '') + '.</p>';
				return;
			}
			var cartes = res.data.map(function (m) {
				var titre = titreDe(m);
				var image = (m.mime_type || '').indexOf('image/') === 0;
				return '<li class="g-medium"><a class="g-medium__vignette" href="#mediatheque/' + m.id + '" aria-label="Modifier ' + esc(titre) + '">' +
					(image ? '<img src="' + esc(vignette(m, 'medium')) + '" alt="" loading="lazy">' : '<span class="g-medium__type">' + esc(extension(m.source_url)) + '</span>') + '</a>' +
					'<div class="g-medium__infos"><strong title="' + esc(titre) + '">' + esc(titre) + '</strong><small>' + esc(dateFr(m.date)) + ' · ' + esc(extension(m.source_url)) + '</small></div>' +
					'<div class="g-actions"><a href="#mediatheque/' + m.id + '">Modifier</a><button type="button" data-copier="' + esc(m.source_url) + '">Copier le lien</button>' +
					'<button type="button" class="g-danger" data-suppr="' + m.id + '" data-titre="' + esc(titre) + '">Supprimer</button></div></li>';
			}).join('');
			var pagination = res.pages > 1
				? '<nav class="g-pagination" aria-label="Pages"><button type="button" data-page="-1"' + (etat.page <= 1 ? ' disabled' : '') + '>Précédent</button><span>Page ' + etat.page + ' sur ' + res.pages + '</span><button type="button" data-page="1"' + (etat.page >= res.pages ? ' disabled' : '') + '>Suivant</button></nav>'
				: '';
			zone.innerHTML = '<ul class="g-medias">' + cartes + '</ul>' + pagination + '<p class="g-total">' + res.total + ' fichier' + (res.total > 1 ? 's' : '') + '</p>';
			[].forEach.call(zone.querySelectorAll('[data-page]'), function (b) {
				b.addEventListener('click', function () { etat.page += parseInt(b.getAttribute('data-page'), 10); chargerMedias(); });
			});
		}).catch(function (e) {
			zone.innerHTML = '<p class="g-erreur" role="alert">' + esc(e.message) + '</p>';
		});
	}

	/* ---------- Universités et villes de la carte (route sueb/v1/carte) ---------- */

	function carte(r) {
		contenu.innerHTML = '<p class="g-chargement"><span class="g-roue"></span>Chargement…</p>';
		api('sueb/v1/carte').then(function (res) {
			dessinerCarte(r, res.data);
		}).catch(function (e) {
			contenu.innerHTML = '<p class="g-erreur" role="alert">' + esc(e.message) + '</p>';
		});
	}

	/* ---------- Contacts et sites (route sueb/v1/contacts) ---------- */

	function contactsSites(r) {
		contenu.innerHTML = '<p class="g-chargement"><span class="g-roue"></span>Chargement…</p>';
		api('sueb/v1/contacts').then(function (res) {
			dessinerContacts(r, res.data);
		}).catch(function (e) {
			contenu.innerHTML = '<p class="g-erreur" role="alert">' + esc(e.message) + '</p>';
		});
	}

	function champLigne(col, valeur, libelle, extra) {
		return '<td><input type="' + (col === 'courriel' ? 'email' : 'text') + '" data-col="' + col + '" aria-label="' + esc(libelle) + '" value="' + esc(valeur == null ? '' : valeur) + '"' + (extra || '') + '></td>';
	}

	function ligneService(s) {
		return '<tr>' + champLigne('nom', s.nom, 'Nom du service') + champLigne('lieu', s.lieu, 'Lieu ou adresse') + champLigne('tel', s.tel, 'Téléphone') +
			champLigne('courriel', s.courriel, 'Courriel') + champLigne('lat', s.lat, 'Latitude', ' inputmode="decimal" size="8"') + champLigne('lon', s.lon, 'Longitude', ' inputmode="decimal" size="8"') + boutonsLigne(false) + '</tr>';
	}

	function ligneEtab(e) {
		return '<tr><th scope="row"><input type="hidden" data-col="id" value="' + e.id + '"><strong>' + esc(e.sigle) + '</strong><span class="g-sous-ligne">' + esc(e.nom) + '</span></th>' +
			champLigne('tel', e.tel, 'Téléphone de ' + e.sigle) + champLigne('courriel', e.courriel, 'Courriel de ' + e.sigle) + champLigne('adresse', e.adresse, 'Adresse de ' + e.sigle) +
			champLigne('lat', e.lat, 'Latitude de ' + e.sigle, ' inputmode="decimal" size="8"') + champLigne('lon', e.lon, 'Longitude de ' + e.sigle, ' inputmode="decimal" size="8"') + '</tr>';
	}

	function dessinerContacts(r, d) {
		var rc = d.rectorat;
		var aidePos = 'Latitude et longitude en degrés décimaux (ex. 2,9157 et 11,1543) : dans Google Maps, un clic droit sur le lieu affiche ses coordonnées, un clic dessus les copie.';
		contenu.innerHTML = entete(r.titre, description(r), '<a class="g-btn" href="' + esc(C.site.replace(/\/?$/, '/')) + 'contact/" target="_blank" rel="noopener">' + ico('externe') + 'Voir la page Contact</a>') +
			'<form class="g-form g-form--large" novalidate>' +
			'<div class="g-section"><h3>Rectorat</h3><p class="g-aide">Repris dans le pied de page de tout le site et en tête de la page Contact.</p></div>' +
			'<div class="g-panneau g-contacts-rectorat">' +
				'<div class="g-champ"><label for="rc-bp">Boîte postale</label><input type="text" id="rc-bp" data-rc="bp" value="' + esc(rc.bp) + '"></div>' +
				'<div class="g-champ"><label for="rc-ville">Ville et région</label><input type="text" id="rc-ville" data-rc="ville" value="' + esc(rc.ville) + '"></div>' +
				'<div class="g-champ"><label for="rc-tel">Téléphone</label><input type="text" id="rc-tel" data-rc="tel" value="' + esc(rc.tel) + '"></div>' +
				'<div class="g-champ"><label for="rc-mail">Courriel</label><input type="email" id="rc-mail" data-rc="courriel" value="' + esc(rc.courriel) + '"></div>' +
				'<div class="g-champ"><label for="rc-lat">Latitude</label><input type="text" inputmode="decimal" id="rc-lat" data-rc="lat" value="' + esc(rc.lat) + '"></div>' +
				'<div class="g-champ"><label for="rc-lon">Longitude</label><input type="text" inputmode="decimal" id="rc-lon" data-rc="lon" value="' + esc(rc.lon) + '"></div>' +
			'</div>' +
			'<div class="g-section"><h3>Services centraux</h3><p class="g-aide">CMS, œuvres universitaires, scolarité, bibliothèque… Chacun apparaît sur la carte avec ses contacts ; sans téléphone ni courriel, la page renvoie au rectorat. ' + esc(aidePos) + '</p></div>' +
			'<div class="g-tableau"><table class="g-lignes" data-liste="services"><thead><tr><th scope="col">Service</th><th scope="col">Lieu ou adresse</th><th scope="col">Téléphone</th><th scope="col">Courriel</th><th scope="col">Latitude</th><th scope="col">Longitude</th><th scope="col"><span class="sr">Actions</span></th></tr></thead><tbody>' +
			d.services.map(ligneService).join('') + '</tbody></table></div>' +
			'<p><button type="button" class="g-btn" data-ajouter="services">' + ico('plus') + 'Ajouter un service</button></p>' +
			'<div class="g-section"><h3>Établissements</h3><p class="g-aide">Les mêmes champs que la section Contact de chaque fiche d’établissement. Sans position, l’établissement est placé au centre de sa ville.</p></div>' +
			(d.etablissements.length
				? '<div class="g-tableau"><table class="g-lignes" data-liste="etablissements"><thead><tr><th scope="col">Établissement</th><th scope="col">Téléphone</th><th scope="col">Courriel</th><th scope="col">Adresse</th><th scope="col">Latitude</th><th scope="col">Longitude</th></tr></thead><tbody>' + d.etablissements.map(ligneEtab).join('') + '</tbody></table></div>'
				: '<p class="g-vide">Aucune fiche d’établissement modifiable avec ce compte.</p>') +
			'<div class="g-form__actions"><button class="g-btn g-btn--primaire" type="submit">Enregistrer</button></div></form>';
		var form = contenu.querySelector('form');

		form.addEventListener('click', function (e) {
			var b = e.target.closest('button');
			if (!b) return;
			var tr = b.closest('tr');
			if (b.hasAttribute('data-ajouter')) {
				var corps = form.querySelector('[data-liste="services"] tbody');
				corps.insertAdjacentHTML('beforeend', ligneService({ nom: '', lieu: 'Ebolowa', tel: '', courriel: '', lat: rc.lat, lon: rc.lon }));
				corps.lastElementChild.querySelector('input').focus();
			} else if (b.hasAttribute('data-retirer-ligne')) {
				tr.parentNode.removeChild(tr);
			} else if (b.hasAttribute('data-bouger')) {
				var voisin = b.getAttribute('data-bouger') === '-1' ? tr.previousElementSibling : tr.nextElementSibling;
				if (!voisin) return;
				tr.parentNode.insertBefore(tr, b.getAttribute('data-bouger') === '-1' ? voisin : voisin.nextElementSibling);
				b.focus();
			}
		});
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var lignes = function (liste) {
				return [].map.call(form.querySelectorAll('[data-liste="' + liste + '"] tbody tr'), function (tr) {
					var o = {};
					[].forEach.call(tr.querySelectorAll('[data-col]'), function (el) { o[el.getAttribute('data-col')] = el.value.trim(); });
					return o;
				});
			};
			var rectorat = {};
			[].forEach.call(form.querySelectorAll('[data-rc]'), function (el) { rectorat[el.getAttribute('data-rc')] = el.value.trim(); });
			var bouton = form.querySelector('button[type=submit]');
			bouton.disabled = true;
			api('sueb/v1/contacts', { method: 'POST', body: { rectorat: rectorat, services: lignes('services'), etablissements: lignes('etablissements') } }).then(function () {
				notifier('Contacts enregistrés.');
			}).catch(function (err) {
				notifier(err.message, true);
			}).then(function () { bouton.disabled = false; });
		});
	}

	function boutonsLigne(fixe) {
		return '<td class="g-ligne__actions"><button type="button" data-bouger="-1" aria-label="Monter">↑</button><button type="button" data-bouger="1" aria-label="Descendre">↓</button>' +
			(fixe ? '' : '<button type="button" class="g-danger" data-retirer-ligne aria-label="Retirer la ligne">✕</button>') + '</td>';
	}

	function ligneVille(v) {
		var centre = v.nom === 'Ebolowa';
		return '<tr><td><input type="text" data-col="nom" aria-label="Ville" value="' + esc(v.nom) + '"' + (centre ? ' readonly title="Centre du réseau : ne peut pas être renommée."' : '') + '></td>' +
			'<td><input type="text" inputmode="decimal" data-col="lat" aria-label="Latitude" value="' + esc(v.lat) + '"></td>' +
			'<td><input type="text" inputmode="decimal" data-col="lon" aria-label="Longitude" value="' + esc(v.lon) + '"></td>' + boutonsLigne(centre) + '</tr>';
	}

	function ligneInstitution(i) {
		var types = [['Université', 'Université'], ['Convention', 'Ministère, convention']].map(function (t) {
			return '<option value="' + t[0] + '"' + (i.type === t[0] ? ' selected' : '') + '>' + t[1] + '</option>';
		}).join('');
		return '<tr><td><input type="hidden" data-col="id" value="' + esc(i.id) + '"><input type="text" data-col="nom" aria-label="Nom" value="' + esc(i.nom) + '"></td>' +
			'<td><input type="text" data-col="sigle" aria-label="Sigle" value="' + esc(i.sigle) + '" size="8"></td>' +
			'<td><select data-col="type" aria-label="Type">' + types + '</select></td>' +
			'<td><input type="text" data-col="ville" list="g-villes" aria-label="Ville" value="' + esc(i.ville) + '" size="10"></td>' +
			'<td><div class="g-media g-media--mini"><input type="hidden" data-col="logo" value="' + (parseInt(i.logo, 10) || '') + '"><div class="g-media__apercu"></div>' +
			'<label class="g-media__choisir">Logo<input type="file" accept="image/*"></label><button type="button" class="g-media__retirer" hidden>Retirer</button><span class="g-media__etat" role="status"></span></div></td>' +
			boutonsLigne(false) + '</tr>';
	}

	function dessinerCarte(r, d) {
		var c = d.cadre;
		contenu.innerHTML = entete(r.titre, description(r)) +
			'<form class="g-form g-form--large" novalidate>' +
			'<div class="g-section"><h3>Universités et ministère partenaires</h3><p class="g-aide">Affichés dans la section Partenariats de l’accueil, avant les conventions. Une ville qui figure dans la liste ci-dessous les place sur la carte. Sans logo, le sigle s’affiche (ou le logo livré avec le thème, s’il existe).</p></div>' +
			'<div class="g-tableau"><table class="g-lignes" data-liste="institutions"><thead><tr><th scope="col">Nom</th><th scope="col">Sigle</th><th scope="col">Type</th><th scope="col">Ville</th><th scope="col">Logo</th><th scope="col"><span class="sr">Actions</span></th></tr></thead><tbody>' +
			d.institutions.map(ligneInstitution).join('') + '</tbody></table></div>' +
			'<p><button type="button" class="g-btn" data-ajouter="institutions">Ajouter une institution</button></p>' +
			'<div class="g-section"><h3>Villes de la carte</h3><p class="g-aide">Latitude entre ' + c.lat[0] + ' et ' + c.lat[1] + ', longitude entre ' + c.lon[0] + ' et ' + c.lon[1] + ' (en degrés décimaux, ex. 2,9157). Ebolowa, centre du réseau, est toujours présente.</p></div>' +
			'<div class="g-tableau"><table class="g-lignes" data-liste="villes"><thead><tr><th scope="col">Ville</th><th scope="col">Latitude</th><th scope="col">Longitude</th><th scope="col"><span class="sr">Actions</span></th></tr></thead><tbody>' +
			d.villes.map(ligneVille).join('') + '</tbody></table></div>' +
			'<p><button type="button" class="g-btn" data-ajouter="villes">Ajouter une ville</button></p>' +
			'<datalist id="g-villes"></datalist>' +
			'<div class="g-form__actions"><button class="g-btn g-btn--primaire" type="submit">Enregistrer</button></div></form>';
		var form = contenu.querySelector('form');

		function majVilles() {
			document.getElementById('g-villes').innerHTML = [].map.call(form.querySelectorAll('[data-liste="villes"] [data-col="nom"]'), function (i) {
				return i.value.trim() ? '<option value="' + esc(i.value.trim()) + '">' : '';
			}).join('');
		}
		majVilles();
		apercusMedias(form);

		form.addEventListener('input', function (e) {
			if (e.target.closest('[data-liste="villes"]')) majVilles();
		});
		form.addEventListener('change', function (e) {
			if (e.target.type === 'file' && e.target.files[0]) envoyerMedia(e.target);
		});
		form.addEventListener('click', function (e) {
			var b = e.target.closest('button');
			if (!b) return;
			if (b.classList.contains('g-media__retirer')) return retirerMedia(b);
			var tr = b.closest('tr');
			if (b.hasAttribute('data-ajouter')) {
				var liste = b.getAttribute('data-ajouter');
				var corps = form.querySelector('[data-liste="' + liste + '"] tbody');
				corps.insertAdjacentHTML('beforeend', liste === 'villes' ? ligneVille({ nom: '', lat: '', lon: '' }) : ligneInstitution({ id: '', nom: '', sigle: '', type: 'Université', ville: '', logo: 0 }));
				corps.lastElementChild.querySelector('input[type=text]').focus();
			} else if (b.hasAttribute('data-retirer-ligne')) {
				tr.parentNode.removeChild(tr);
				majVilles();
			} else if (b.hasAttribute('data-bouger')) {
				var voisin = b.getAttribute('data-bouger') === '-1' ? tr.previousElementSibling : tr.nextElementSibling;
				if (!voisin) return;
				tr.parentNode.insertBefore(tr, b.getAttribute('data-bouger') === '-1' ? voisin : voisin.nextElementSibling);
				b.focus();
			}
		});
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var lireLignes = function (liste) {
				return [].map.call(form.querySelectorAll('[data-liste="' + liste + '"] tbody tr'), function (tr) {
					var o = {};
					[].forEach.call(tr.querySelectorAll('[data-col]'), function (el) { o[el.getAttribute('data-col')] = el.value.trim(); });
					return o;
				});
			};
			var bouton = form.querySelector('button[type=submit]');
			bouton.disabled = true;
			api('sueb/v1/carte', { method: 'POST', body: { villes: lireLignes('villes'), institutions: lireLignes('institutions') } }).then(function () {
				notifier('Enregistré.');
				carte(r); // recharge : identifiants et sigles complétés par le serveur
			}).catch(function (err) {
				notifier(err.message, true);
				bouton.disabled = false;
			});
		});
	}

	/* ---------- Formulaire ---------- */

	function chargerListes(r) {
		var attentes = r.champs.filter(function (f) { return f.type === 'taxo' && !listes[f.endpoint]; }).map(function (f) {
			return toutCharger('wp/v2/' + f.endpoint + '?_fields=id,name&hide_empty=false').then(function (l) { listes[f.endpoint] = l; });
		});
		if (!listes.__personnes && r.champs.some(function (f) { return f.type === 'personnes'; })) {
			/* Sans droit sur le personnel (formations seules), l'annuaire public suffit pour choisir les enseignants d'une UE. */
			attentes.push(toutCharger('wp/v2/ueb_personnel?' + (a('sueb_personnel') ? 'context=edit&status=publish,draft&' : '') + 'orderby=title&order=asc&_fields=id,title,meta').then(function (l) { listes.__personnes = l; }));
		}
		var besoinArbre = r.champs.some(function (f) { return f.type === 'parent' || f.type === 'etablissement' || f.type === 'departement'; });
		if (besoinArbre || r.arbre) attentes.push(chargerArbre());
		return Promise.all(attentes);
	}

	function formulaire(r, id) {
		contenu.innerHTML = '<p class="g-chargement"><span class="g-roue"></span>Chargement…</p>';
		var vide = Object.assign({ status: 'publish', format: 'standard', menu_order: 0, meta: {} }, r.defauts || {});
		var lecture = id ? api('wp/v2/' + r.endpoint + '/' + id + '?context=edit') : Promise.resolve({ data: vide });
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
		if (f.type === 'apercu') {
			var mime = p.mime_type || '';
			var src = p.source_url || '';
			var vue = mime.indexOf('image/') === 0 ? '<img src="' + esc(vignette(p, 'large')) + '" alt="">'
				: mime.indexOf('video/') === 0 ? '<video src="' + esc(src) + '" controls preload="metadata"></video>'
				: mime.indexOf('audio/') === 0 ? '<audio src="' + esc(src) + '" controls preload="metadata"></audio>'
				: '<span class="g-medium__type">' + esc(extension(src)) + '</span>';
			return '<div class="g-champ g-fiche-media"><div class="g-fiche-media__vue">' + vue + '</div><p class="g-aide">Adresse du fichier : <a href="' + esc(src) + '" target="_blank" rel="noopener">' + esc(src) + '</a> ' +
				'<button type="button" class="g-lien-bouton" data-copier="' + esc(src) + '">Copier</button></p></div>';
		}
		if (f.type === 'section') {
			return '<div class="g-section"><h3>' + esc(f.label) + '</h3>' + (f.aide ? '<p class="g-aide">' + esc(f.aide) + '</p>' : '') + '</div>';
		}
		var v = lire(p, f.path);
		var id = 'f-' + f.path.replace(/\W/g, '_');
		var nom = ' data-path="' + esc(f.path) + '"';
		var req = f.required ? ' required' : '';
		var aide = f.aide ? '<p class="g-aide" id="' + id + '-aide">' + esc(f.aide) + '</p>' : '';
		var decrit = f.aide ? ' aria-describedby="' + id + '-aide"' : '';
		var libelle = '<label for="' + id + '">' + esc(f.label) + (f.required ? ' *' : '') + '</label>';
		var h;
		switch (f.type) {
			case 'text':
			case 'url':
			case 'email':
			case 'number':
			case 'date':
				if (f.type === 'number' && (v === 0 || v === '0') && f.path.indexOf('meta.') === 0) v = '';
				h = libelle + '<input type="' + f.type + '" id="' + id + '"' + nom + req + decrit + (f.type === 'number' ? ' min="0" step="1"' : '') + ' value="' + esc(v == null ? '' : v) + '">';
				break;
			case 'color':
				h = libelle + '<input type="color" id="' + id + '"' + nom + decrit + ' value="' + esc(v || '#0f2c1f') + '">';
				break;
			case 'textarea':
				h = libelle + '<textarea id="' + id + '"' + nom + decrit + ' rows="' + (f.lignes || (f.path.indexOf('meta.') === 0 ? 5 : 3)) + '">' + esc(v || '') + '</textarea>';
				break;
			case 'datetime':
				h = libelle + '<input type="datetime-local" id="' + id + '"' + nom + decrit + ' value="' + esc(String(v || '').slice(0, 16)) + '">';
				break;
			case 'select':
				h = libelle + '<select id="' + id + '"' + nom + decrit + '>' + f.options.map(function (o) {
					return '<option value="' + esc(o[0]) + '"' + (String(v == null ? '' : v) === o[0] ? ' selected' : '') + '>' + esc(o[1]) + '</option>';
				}).join('') + '</select>';
				break;
			case 'parent':
				if (f.genre) {
					/* Ce qu'on ajoute d'abord, puis son rattachement au niveau juste au-dessus. */
					var noeud = arbre.par[p.id];
					var pere = arbre.par[v];
					var genre = Math.min(3, Math.max(1, noeud ? noeud.prof : (pere ? pere.prof + 1 : 2)));
					h = '<label for="f-genre">' + (p.id ? 'Il s’agit de' : 'Vous ajoutez') + '</label><select id="f-genre" data-genre>' + [1, 2, 3].map(function (g) {
						return '<option value="' + g + '"' + (g === genre ? ' selected' : '') + '>' + esc(GENRES[g][0]) + '</option>';
					}).join('') + '</select>' +
						'<label for="' + id + '" class="g-label-suite" data-libelle-parent>' + esc(GENRES[genre][1]) + ' *</label>' +
						'<select id="' + id + '"' + nom + req + ' data-courant="' + (p.id || '') + '">' + optionsRattachement(genre, v, p.id) + '</select>';
					break;
				}
				/* falls through */
			case 'etablissement':
			case 'departement':
				h = libelle + '<select id="' + id + '"' + nom + req + decrit + '>' + optionsStructures(f, v, p.id) + '</select>';
				break;
			case 'niveaux':
				h = '<fieldset class="g-cases g-niveaux-ouverts-cadre"><legend>' + esc(f.label) + '</legend><div class="g-niveaux-ouverts"' + nom + ' data-enregistres="' + esc(v || '') + '"></div></fieldset>';
				aide = '';
				break;
			case 'taxo':
				var termes = listes[f.endpoint] || [];
				if (f.multiple) {
					h = '<fieldset class="g-cases" id="' + id + '"' + nom + decrit + '><legend>' + esc(f.label) + '</legend>' + (termes.length ? termes.map(function (o) {
						return '<label><input type="checkbox" value="' + o.id + '"' + ((v || []).indexOf(o.id) > -1 ? ' checked' : '') + '> ' + esc(o.name) + '</label>';
					}).join('') : '<span class="g-aide">Aucune catégorie n’existe encore.</span>') + '</fieldset>';
				} else {
					h = libelle + '<select id="' + id + '"' + nom + decrit + '><option value="">— Aucune —</option>' + termes.map(function (o) {
						return '<option value="' + o.id + '"' + ((v || [])[0] === o.id ? ' selected' : '') + '>' + esc(o.name) + '</option>';
					}).join('') + '</select>';
				}
				break;
			case 'richtext':
				/* Contenu fait de blocs avancés (colonnes, boutons…) : l'éditeur simple les abîmerait, il reste en lecture seule. */
				if (p.sueb_gestion && !p.sueb_gestion.simple) {
					h = '<span class="g-label">' + esc(f.label) + '</span><p class="g-protege" role="note">Cette page contient des blocs avancés (colonnes, boutons…). Pour ne pas les abîmer, son contenu se modifie dans l’éditeur WordPress, par un administrateur. Les autres champs restent modifiables ici.</p>' +
						'<div class="g-apercu-contenu">' + ((p.content && p.content.rendered) || '') + '</div>';
					break;
				}
				h = '<span class="g-label" id="' + id + '-l">' + esc(f.label) + '</span><div class="g-riche"><div class="g-riche__barre" role="toolbar" aria-label="Mise en forme du texte">' + barreOutils() + '</div>' +
					'<input type="file" class="g-riche__fichier" accept="image/*" hidden>' +
					'<input type="file" class="g-riche__document" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.zip" hidden>' +
					'<div class="g-riche__zone" id="' + id + '" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="' + id + '-l"' + nom + '>' + enParagraphes(v) + '</div>' +
					'<p class="g-riche__pied" aria-live="polite"></p></div>';
				break;
			case 'image':
			case 'fichier':
				var accepte = f.accept || (f.type === 'image' ? 'image/*' : 'video/*,audio/*');
				h = '<span class="g-label">' + esc(f.label) + '</span><div class="g-media" data-type="' + f.type + '"><input type="hidden"' + nom + ' value="' + (parseInt(v, 10) || '') + '">' +
					'<div class="g-media__apercu"></div><label class="g-media__choisir">' + (f.type === 'image' ? 'Choisir une image' : 'Choisir un fichier') +
					'<input type="file" accept="' + esc(accepte) + '"></label><button type="button" class="g-media__retirer" hidden>Retirer</button><span class="g-media__etat" role="status"></span></div>';
				break;
			case 'personnes':
				/* Les enseignants d'une UE : recherche, puis cases à cocher (les personnes choisies en tête). */
				var choisis = String(v || '').split(/[^\d]+/).filter(Boolean).map(Number);
				var gens = (listes.__personnes || []).slice().sort(function (x, y) {
					return (choisis.indexOf(y.id) > -1) - (choisis.indexOf(x.id) > -1);
				});
				var sigleDe = function (pers) {
					var e = pers.meta && parseInt(pers.meta._sueb_etablissement, 10);
					var n = e && arbre && arbre.par[e];
					return n ? (n.sigle || n.nom) : '';
				};
				h = '<span class="g-label" id="' + id + '-l">' + esc(f.label) + '</span><div class="g-personnes" role="group" aria-labelledby="' + id + '-l"' + nom + '>' +
					'<div class="g-personnes__tete"><input type="search" class="g-personnes__filtre" placeholder="Rechercher un nom" aria-label="Rechercher un enseignant"><span class="g-personnes__compte" aria-live="polite">' + choisis.length + ' choisi' + (choisis.length > 1 ? 's' : '') + '</span></div>' +
					'<div class="g-personnes__liste">' + (gens.length ? gens.map(function (pers) {
						var t = (pers.title && (pers.title.raw || pers.title.rendered)) || '';
						return '<label data-nom="' + esc(t.toLowerCase()) + '"><input type="checkbox" value="' + pers.id + '"' + (choisis.indexOf(pers.id) > -1 ? ' checked' : '') + '><span>' + esc(t) + '</span>' + (sigleDe(pers) ? '<small>' + esc(sigleDe(pers)) + '</small>' : '') + '</label>';
					}).join('') : '<p class="g-aide">Aucune fiche dans le Personnel pour le moment.</p>') + '</div></div>';
				break;
			case 'case':
				h = '<span class="g-label">' + esc(f.label) + '</span><label class="g-case" for="' + id + '"><input type="checkbox" id="' + id + '"' + nom + decrit + (v ? ' checked' : '') + '> ' + esc(f.texte || f.label) + '</label>';
				break;
		}
		var niveaux = f.niveaux ? ' data-niveaux="' + f.niveaux.join(',') + '"' : '';
		return '<div class="g-champ g-champ--' + f.type + '"' + niveaux + '>' + h + aide + '</div>';
	}

	/* Champs « image » et « fichier » : envoi dans la médiathèque, aperçu, retrait. */
	function envoyerMedia(input) {
		var zone = input.closest('.g-media');
		var etatZone = zone.querySelector('.g-media__etat');
		var fd = new FormData();
		fd.append('file', input.files[0], input.files[0].name);
		etatZone.textContent = 'Envoi en cours…';
		api('wp/v2/media', { method: 'POST', body: fd }).then(function (res) {
			zone.querySelector('input[type=hidden]').value = res.data.id;
			apercu(zone, res.data);
			etatZone.textContent = '';
		}).catch(function (err) {
			etatZone.textContent = '';
			notifier('Envoi impossible : ' + err.message, true);
		});
		input.value = '';
	}

	function retirerMedia(bouton) {
		var z = bouton.closest('.g-media');
		z.querySelector('input[type=hidden]').value = '';
		z.querySelector('.g-media__apercu').innerHTML = '';
		bouton.hidden = true;
	}

	function apercusMedias(racine) {
		[].forEach.call(racine.querySelectorAll('.g-media'), function (zone) {
			var mid = parseInt(zone.querySelector('input[type=hidden]').value, 10);
			if (mid) api('wp/v2/media/' + mid + '?context=edit&_fields=id,source_url,mime_type,media_details').then(function (res) { apercu(zone, res.data); }).catch(function () {});
		});
	}

	function apercu(zone, media) {
		var cible = zone.querySelector('.g-media__apercu');
		var src = (media.media_details && media.media_details.sizes && media.media_details.sizes.medium && media.media_details.sizes.medium.source_url) || media.source_url;
		cible.innerHTML = (media.mime_type || '').indexOf('image/') === 0
			? '<img src="' + esc(src) + '" alt="">'
			: '<a href="' + esc(media.source_url) + '" target="_blank" rel="noopener">' + esc(String(media.source_url).split('/').pop()) + '</a>';
		zone.querySelector('.g-media__retirer').hidden = false;
	}

	/* Pour les structures, n'affiche que les champs utiles au niveau (établissement, département, filière, UE). */
	function majNiveau(r, form) {
		var niveau = r.niveauFixe;
		if (niveau == null) {
			var sel = form.querySelector('[data-path="parent"]');
			var parent = sel && arbre && arbre.par[sel.value];
			var genre = form.querySelector('[data-genre]');
			niveau = genre ? parseInt(genre.value, 10) : (parent ? parent.prof + 1 : -1);
		}
		[].forEach.call(form.querySelectorAll('[data-niveaux]'), function (c) {
			c.hidden = niveau > -1 && c.getAttribute('data-niveaux').split(',').indexOf(String(niveau)) === -1;
		});
		var info = form.querySelector('.g-niveau');
		if (info) info.textContent = niveau > -1 && !form.querySelector('[data-genre]') ? 'Niveau : ' + NIVEAUX[niveau] : '';
	}

	function dessiner(r, id, p) {
		var tete = r.reglages
			? entete(r.titre, description(r))
			: entete((id ? 'Modifier ' : 'Ajouter ') + r.singulier, id ? titreDe(p) : '', '', ['#' + r.id, r.titre]);
		var role = p.sueb_gestion && p.sueb_gestion.role
			? '<p class="g-aide g-role">Utilisée par le site : ' + esc(p.sueb_gestion.role) + '. Son adresse ne change pas et elle ne peut pas être supprimée.</p>'
			: '';
		contenu.innerHTML = tete + role +
			'<form class="g-form g-panneau" novalidate>' + r.champs.map(function (f) {
				return champ(f, p) + (f.type === 'parent' ? '<p class="g-niveau" aria-live="polite"></p>' : '');
			}).join('') +
			'<div class="g-form__actions"><button class="g-btn g-btn--primaire" type="submit">Enregistrer</button>' + (r.reglages ? '' : '<a class="g-btn" href="#' + r.id + '">Annuler</a>') + '</div></form>';
		var form = contenu.querySelector('form');

		try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) { /* sans effet */ }

		if (form.querySelector('[data-niveaux]')) majNiveau(r, form);
		majNiveauxOuverts(form, true);

		apercusMedias(form); // aperçu des médias déjà choisis

		[].forEach.call(form.querySelectorAll('.g-riche__zone'), function (zone) {
			['keyup', 'mouseup', 'focusout'].forEach(function (ev) {
				zone.addEventListener(ev, function () { garderSelection(zone); });
			});
			compter(zone);
			zone.addEventListener('input', function () { compter(zone); });
			zone.addEventListener('paste', function (e) {
				var html = e.clipboardData && e.clipboardData.getData('text/html');
				if (!html) return; // texte brut : collé tel quel
				e.preventDefault();
				document.execCommand('insertHTML', false, nettoyerCollage(html));
				compter(zone);
			});
			zone.addEventListener('keydown', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); lien(zone); }
				if (e.key === 'Escape' && zone.closest('.g-riche--plein')) commande(zone, 'plein-ecran');
			});
		});
		[].forEach.call(form.querySelectorAll('[data-bloc-choix]'), function (choix) {
			choix.addEventListener('change', function () {
				var zone = choix.closest('.g-riche').querySelector('.g-riche__zone');
				restaurer(zone, zone._range);
				commande(zone, 'bloc', choix.value);
			});
		});
		if (!fermerPalettes.ecoute) {
			fermerPalettes.ecoute = true;
			document.addEventListener('click', function (e) {
				if (!e.target.closest('.g-riche__outil')) fermerPalettes();
			});
		}

		form.addEventListener('mousedown', function (e) {
			if (e.target.closest('[data-cmd]')) e.preventDefault(); // garde la sélection dans l'éditeur
		});
		form.addEventListener('click', function (e) {
			var b = e.target.closest('[data-cmd]');
			if (b) {
				commande(b.closest('.g-riche').querySelector('.g-riche__zone'), b.getAttribute('data-cmd'), b.getAttribute('data-valeur'));
				return;
			}
			var x = e.target.closest('.g-media__retirer');
			if (x) retirerMedia(x);
			var cp = e.target.closest('[data-copier]');
			if (cp) copier(cp.getAttribute('data-copier'));
		});
		form.addEventListener('input', function (e) {
			if (!e.target.classList.contains('g-personnes__filtre')) return;
			var q = e.target.value.trim().toLowerCase();
			[].forEach.call(e.target.closest('.g-personnes').querySelectorAll('label[data-nom]'), function (l) {
				l.hidden = q && l.getAttribute('data-nom').indexOf(q) === -1;
			});
		});
		form.addEventListener('change', function (e) {
			var groupe = e.target.closest('.g-personnes');
			if (groupe) {
				var n = groupe.querySelectorAll('input[type=checkbox]:checked').length;
				groupe.querySelector('.g-personnes__compte').textContent = n + ' choisi' + (n > 1 ? 's' : '');
				return;
			}
			if (e.target.hasAttribute('data-genre')) {
				/* On change ce qu'on ajoute : la liste de rattachement et son libellé suivent. */
				var g = parseInt(e.target.value, 10);
				var rattache = form.querySelector('[data-path="parent"]');
				rattache.innerHTML = optionsRattachement(g, '', parseInt(rattache.getAttribute('data-courant'), 10) || 0);
				form.querySelector('[data-libelle-parent]').textContent = GENRES[g][1] + ' *';
				return majNiveau(r, form);
			}
			if (e.target.getAttribute('data-path') === 'meta._sueb_parcours') return majNiveauxOuverts(form, false);
			if (e.target.getAttribute('data-path') === 'parent') return majNiveau(r, form);
			if (e.target.classList.contains('g-riche__fichier')) return insererImage(e.target);
			if (e.target.classList.contains('g-riche__document')) return insererDocument(e.target);
			if (e.target.type === 'file' && e.target.files[0]) envoyerMedia(e.target);
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var manquant = r.champs.filter(function (f) {
				var el = f.required && form.querySelector('[data-path="' + f.path + '"]');
				return el && !String(el.value).trim();
			})[0];
			if (manquant) {
				notifier('Le champ « ' + manquant.label + ' » est obligatoire.', true);
				form.querySelector('[data-path="' + manquant.path + '"]').focus();
				return;
			}
			var bouton = form.querySelector('button[type=submit]');
			bouton.disabled = true;
			var cible = r.reglages ? 'sueb/v1/reglages' : 'wp/v2/' + r.endpoint + (id ? '/' + id : '');
			api(cible, { method: 'POST', body: collecter(r, form) }).then(function () {
				notifier('Enregistré.');
				if (r.reglages) {
					bouton.disabled = false;
					return;
				}
				if (r.arbre || r.endpoint === 'ueb_structure') arbre = null;
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
			/* Un champ masqué (sans objet à ce niveau) n'est pas envoyé : sa valeur éventuelle reste telle quelle. */
			if (el.closest('.g-champ').hidden) return;
			var estMeta = f.path.indexOf('meta.') === 0;
			var v = f.type === 'richtext' ? el.innerHTML.replace(/^(<p><br><\/p>)+$/, '').trim() : f.type === 'case' ? el.checked : el.value;
			if (f.type === 'niveaux') {
				v = [].map.call(el.querySelectorAll('input:checked'), function (c) { return c.value; }).join(', ');
			} else if (f.type === 'personnes') {
				var ids = [].map.call(el.querySelectorAll('input[type=checkbox]:checked'), function (c) { return c.value; });
				v = ids.length ? ',' + ids.join(',') + ',' : '';
			} else if (f.type === 'taxo') {
				v = f.multiple
					? [].map.call(el.querySelectorAll('input:checked'), function (c) { return parseInt(c.value, 10); })
					: (v ? [parseInt(v, 10)] : []);
			} else if (estMeta) {
				v = v == null ? '' : String(v); // les métas sont enregistrées en texte
			} else if (f.type === 'image' || f.type === 'fichier' || f.type === 'number' || f.type === 'parent') {
				v = parseInt(v, 10) || 0;
			} else if (f.type === 'datetime') {
				if (!v) return;
				if (v.length === 16) v += ':00';
			}
			if (estMeta) meta[f.path.slice(5)] = v;
			else charge[f.path] = v;
		});
		if (Object.keys(meta).length) charge.meta = meta;
		if (r.defauts) Object.keys(r.defauts).forEach(function (k) { if (!(k in charge)) charge[k] = r.defauts[k]; });
		return charge;
	}

	/* ---------- Rôles et comptes (inc/roles.php : routes sueb/v1/comptes et sueb/v1/roles) ----------
	   Comme la plateforme d'inscription : un rôle = des permissions + une portée ; un compte = un rôle
	   (+ son établissement si la portée est « celui du compte »). On n'attribue jamais plus que ses droits :
	   les permissions et établissements hors de sa portée sont grisés, et le serveur le vérifie. */

	function comptes(r, sous) {
		contenu.innerHTML = '<p class="g-chargement"><span class="g-roue"></span>Chargement…</p>';
		api('sueb/v1/comptes').then(function (res) {
			var d = res.data;
			if (sous[0] === 'role') return formRole(r, d, sous[1]);
			if (sous[0] === 'compte') return formCompte(r, d, sous[1]);
			listeComptes(r, d, sous[0] === 'roles' ? 'roles' : 'comptes');
		}).catch(function (e) {
			contenu.innerHTML = '<p class="g-erreur" role="alert">' + esc(e.message) + '</p>';
		});
	}

	function etabDe(d, id) {
		var e = d.etablissements.filter(function (x) { return x.id === id; })[0];
		return e ? (e.sigle || e.nom) : '';
	}
	function roleDe(d, slug) { return d.roles.filter(function (x) { return x.slug === slug; })[0]; }
	function dansMaPortee(d, id) { return d.moi.portee === 'tous' || d.moi.portee.indexOf(id) > -1; }
	function porteeTexte(d, role, compte) {
		if (!role) return '—';
		if (role.portee === 'tous') return 'Tous les établissements';
		if (role.portee === 'plusieurs') return role.etablissements.map(function (id) { return etabDe(d, id); }).join(', ');
		return compte ? (etabDe(d, compte.etablissement) || 'Établissement à choisir') : 'L’établissement du compte';
	}

	function ongletsComptes(vue) {
		return '<nav class="g-onglets" aria-label="Rôles et comptes">' +
			'<a href="#comptes"' + (vue === 'comptes' ? ' aria-current="page"' : '') + '>' + ico('personnes') + 'Comptes</a>' +
			'<a href="#comptes/roles"' + (vue === 'roles' ? ' aria-current="page"' : '') + '>' + ico('cle') + 'Rôles</a></nav>';
	}

	function montrerMdp(nom, mdp) {
		return demander({ titre: 'Mot de passe provisoire de ' + nom, texte: 'Il ne sera plus affiché : transmettez-le à la personne, qui se connectera sur la page de gestion avec son identifiant.', champ: mdp, valider: 'C’est noté', icone: 'cle' });
	}

	function listeComptes(r, d, vue) {
		var action = vue === 'roles'
			? '<a class="g-btn g-btn--primaire" href="#comptes/role/new">' + ico('plus') + 'Nouveau rôle</a>'
			: (d.roles.some(function (x) { return x.attribuable; }) ? '<a class="g-btn g-btn--primaire" href="#comptes/compte/new">' + ico('plus') + 'Nouveau compte</a>' : '');
		var corps;
		if (vue === 'roles') {
			corps = '<div class="g-tableau"><table><thead><tr><th scope="col">Rôle</th><th scope="col">Permissions</th><th scope="col">Portée</th><th scope="col">Comptes</th><th scope="col"><span class="sr">Actions</span></th></tr></thead><tbody>' +
				d.roles.map(function (x) {
					var perms = x.permissions.map(function (c) {
						var p = d.permissions.filter(function (y) { return y.cap === c; })[0];
						return '<span class="g-puce">' + esc(p ? p.libelle : c) + '</span>';
					}).join('');
					var actions = x.attribuable
						? '<a href="#comptes/role/' + esc(x.slug) + '">Modifier</a>' + (x.comptes ? '' : '<button type="button" class="g-danger" data-suppr-role="' + esc(x.slug) + '" data-nom="' + esc(x.nom) + '">Supprimer</button>')
						: '<span class="g-aide">Au-delà de vos droits</span>';
					return '<tr><th scope="row">' + esc(x.nom) + '</th><td><div class="g-puces">' + perms + '</div></td><td>' + esc(porteeTexte(d, x)) + '</td><td class="g-court">' + x.comptes + '</td><td class="g-actions">' + actions + '</td></tr>';
				}).join('') + '</tbody></table></div>';
		} else {
			corps = d.comptes.length ? '<div class="g-tableau"><table><thead><tr><th scope="col">Personne</th><th scope="col">Rôle</th><th scope="col">Portée</th><th scope="col">Statut</th><th scope="col"><span class="sr">Actions</span></th></tr></thead><tbody>' +
				d.comptes.map(function (c) {
					var role = roleDe(d, c.role);
					var actions = c.gerable
						? '<a href="#comptes/compte/' + c.id + '">Modifier</a><button type="button" data-mdp="' + c.id + '" data-nom="' + esc(c.nom) + '">Nouveau mot de passe</button>' +
						  (c.suspendu ? '<button type="button" data-etat-compte="reactiver" data-id="' + c.id + '" data-nom="' + esc(c.nom) + '">Réactiver</button>'
							: '<button type="button" class="g-danger" data-etat-compte="suspendre" data-id="' + c.id + '" data-nom="' + esc(c.nom) + '">Suspendre</button>')
						: '<span class="g-aide">' + (c.moi ? 'Votre compte' : c.admin ? 'Administrateur' : 'Au-delà de vos droits') + '</span>';
					return '<tr><th scope="row">' + esc(c.nom) + '<small class="g-sous-ligne">' + esc(c.identifiant) + ' · ' + esc(c.courriel) + '</small></th>' +
						'<td>' + esc(c.admin ? 'Administrateur' : role ? role.nom : 'Sans rôle de gestion') + '</td>' +
						'<td>' + esc(c.admin ? 'Tous les établissements' : porteeTexte(d, role, c)) + '</td>' +
						'<td><span class="g-etat g-etat--' + (c.suspendu ? 'trash' : 'publish') + '">' + (c.suspendu ? 'Suspendu' : 'Actif') + '</span></td>' +
						'<td class="g-actions">' + actions + '</td></tr>';
				}).join('') + '</tbody></table></div>' : '<p class="g-vide">Aucun compte à gérer pour l’instant.</p>';
		}
		contenu.innerHTML = entete(r.titre, vue === 'roles'
			? 'Un rôle réunit des permissions et une portée : tous les établissements, celui du compte, ou une liste choisie.'
			: 'Les personnes qui ont accès à la gestion. Un compte suspendu garde ses contenus mais ne peut plus se connecter.', action) +
			ongletsComptes(vue) + corps;

		contenu.onclick = function (e) {
			var b = e.target.closest('button');
			if (!b) return;
			var nom = b.getAttribute('data-nom');
			if (b.hasAttribute('data-mdp')) {
				demander({ titre: 'Nouveau mot de passe pour ' + nom + ' ?', texte: 'L’ancien ne fonctionnera plus.', valider: 'Générer', icone: 'cle' }).then(function (ok) {
					if (!ok) return;
					api('sueb/v1/comptes/' + b.getAttribute('data-mdp') + '/mdp', { method: 'POST' }).then(function (res) { montrerMdp(nom, res.data.mdp); }).catch(function (err) { notifier(err.message, true); });
				});
			} else if (b.hasAttribute('data-etat-compte')) {
				var act = b.getAttribute('data-etat-compte');
				(act === 'suspendre'
					? demander({ titre: 'Suspendre le compte de ' + nom + ' ?', texte: 'La personne est déconnectée et ne peut plus ouvrir la gestion. Ses contenus restent en ligne ; vous pourrez réactiver le compte.', valider: 'Suspendre', ton: 'danger' })
					: Promise.resolve(true)).then(function (ok) {
					if (!ok) return;
					api('sueb/v1/comptes/' + b.getAttribute('data-id') + '/' + act, { method: 'POST' }).then(function () {
						notifier(act === 'suspendre' ? 'Compte suspendu.' : 'Compte réactivé.');
						comptes(r, []);
					}).catch(function (err) { notifier(err.message, true); });
				});
			} else if (b.hasAttribute('data-suppr-role')) {
				demander({ titre: 'Supprimer le rôle « ' + nom + ' » ?', texte: 'Aucun compte ne le porte. Il disparaîtra de la liste des rôles.', valider: 'Supprimer', ton: 'danger' }).then(function (ok) {
					if (!ok) return;
					api('sueb/v1/roles/' + b.getAttribute('data-suppr-role'), { method: 'DELETE' }).then(function () {
						notifier('Rôle supprimé.');
						comptes(r, ['roles']);
					}).catch(function (err) { notifier(err.message, true); });
				});
			}
		};
	}

	function champTexte(id, label, valeur, extra, aide) {
		return '<div class="g-champ"><label for="' + id + '">' + esc(label) + '</label><input type="text" id="' + id + '" value="' + esc(valeur || '') + '"' + (extra || '') + (aide ? ' aria-describedby="' + id + '-aide"' : '') + '>' +
			(aide ? '<p class="g-aide" id="' + id + '-aide">' + esc(aide) + '</p>' : '') + '</div>';
	}

	function formCompte(r, d, id) {
		var c = id === 'new' ? null : d.comptes.filter(function (x) { return String(x.id) === String(id); })[0];
		if (id !== 'new' && (!c || !c.gerable)) { location.hash = '#comptes'; return; }
		var roles = d.roles.filter(function (x) { return x.attribuable; });
		var etabs = d.etablissements.filter(function (e) { return dansMaPortee(d, e.id); });
		contenu.innerHTML = entete(c ? c.nom : 'Nouveau compte', c ? 'Modifier le compte, son rôle ou son établissement.' : 'Le mot de passe provisoire s’affichera une seule fois, après l’enregistrement.', '', ['#comptes', 'Comptes']) +
			'<form class="g-form" novalidate>' +
			'<div class="g-section"><h3>La personne</h3></div>' +
			champTexte('c-nom', 'Nom affiché *', c && c.nom, ' required autocomplete="off"') +
			champTexte('c-id', 'Identifiant de connexion *', c && c.identifiant, c ? ' disabled' : ' required autocomplete="off" autocapitalize="none" spellcheck="false"', c ? 'L’identifiant ne change pas.' : 'Sans espace ni accent, ex. « jdupont ».') +
			'<div class="g-champ"><label for="c-courriel">Adresse électronique *</label><input type="email" id="c-courriel" required value="' + esc(c ? c.courriel : '') + '"></div>' +
			'<div class="g-section"><h3>Ses droits</h3><p class="g-aide">Seuls les rôles qui restent dans vos propres droits sont proposés.</p></div>' +
			'<div class="g-champ"><label for="c-role">Rôle *</label><select id="c-role" required><option value="">— Choisir —</option>' + roles.map(function (x) {
				return '<option value="' + esc(x.slug) + '"' + (c && c.role === x.slug ? ' selected' : '') + '>' + esc(x.nom) + '</option>';
			}).join('') + '</select><p class="g-aide" id="c-role-aide"></p></div>' +
			'<div class="g-champ" id="c-etab-champ" hidden><label for="c-etab">Établissement *</label><select id="c-etab"><option value="">— Choisir —</option>' + etabs.map(function (e) {
				return '<option value="' + e.id + '"' + (c && c.etablissement === e.id ? ' selected' : '') + '>' + esc((e.sigle ? e.sigle + ' — ' : '') + e.nom) + '</option>';
			}).join('') + '</select></div>' +
			'<div class="g-form__actions"><a class="g-btn" href="#comptes">Annuler</a><button class="g-btn g-btn--primaire" type="submit">' + (c ? 'Enregistrer' : 'Créer le compte') + '</button></div></form>';
		var form = contenu.querySelector('form');
		var choix = form.querySelector('#c-role');
		function majRole() {
			var role = roleDe(d, choix.value);
			form.querySelector('#c-etab-champ').hidden = !role || role.portee !== 'un';
			form.querySelector('#c-role-aide').textContent = role ? role.permissions.map(function (cap) {
				var p = d.permissions.filter(function (y) { return y.cap === cap; })[0];
				return p ? p.libelle : cap;
			}).join(', ') + ' · ' + porteeTexte(d, role) + '.' : '';
		}
		choix.addEventListener('change', majRole);
		majRole();
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var corps = {
				id: c ? c.id : 0,
				nom: form.querySelector('#c-nom').value.trim(),
				identifiant: form.querySelector('#c-id').value.trim(),
				courriel: form.querySelector('#c-courriel').value.trim(),
				role: choix.value,
				etablissement: parseInt(form.querySelector('#c-etab').value, 10) || 0
			};
			var bouton = form.querySelector('[type=submit]');
			bouton.disabled = true;
			api('sueb/v1/comptes', { method: 'POST', body: corps }).then(function (res) {
				notifier(c ? 'Compte enregistré.' : 'Compte créé.');
				(res.data.mdp ? montrerMdp(corps.nom, res.data.mdp) : Promise.resolve()).then(function () { location.hash = '#comptes'; });
			}).catch(function (err) {
				notifier(err.message, true);
				bouton.disabled = false;
			});
		});
	}

	function formRole(r, d, slug) {
		var x = slug === 'new' ? null : roleDe(d, slug);
		if (slug !== 'new' && (!x || !x.attribuable)) { location.hash = '#comptes/roles'; return; }
		var mien = function (cap) { return d.moi.permissions.indexOf(cap) > -1; };
		var groupes = [];
		d.permissions.forEach(function (p) { if (groupes.indexOf(p.groupe) < 0) groupes.push(p.groupe); });
		var cases = groupes.map(function (g) {
			return '<fieldset class="g-cases g-cases--colonne"><legend>' + esc(g) + '</legend>' + d.permissions.filter(function (p) { return p.groupe === g; }).map(function (p) {
				var coche = x && x.permissions.indexOf(p.cap) > -1;
				return '<label class="g-case g-case--detail"><input type="checkbox" name="perm" value="' + esc(p.cap) + '"' + (coche ? ' checked' : '') + (mien(p.cap) ? '' : ' disabled') + '>' +
					'<span><strong>' + esc(p.libelle) + (p.portee ? ' <small class="g-puce">limitée à la portée</small>' : '') + '</strong><small>' + esc(p.aide) + (mien(p.cap) ? '' : ' — vous ne l’avez pas, vous ne pouvez donc pas l’attribuer.') + '</small></span></label>';
			}).join('') + '</fieldset>';
		}).join('');
		var portee = x ? x.portee : 'un';
		var radios = [['tous', 'Tous les établissements', 'Le rôle agit partout.'], ['un', 'L’établissement du compte', 'On choisit l’établissement en créant chaque compte (ex. le webmestre de la FS).'], ['plusieurs', 'Des établissements choisis', 'Le rôle agit sur la liste ci-dessous, pour tous ses comptes.']].map(function (o) {
			var interdit = o[0] === 'tous' && d.moi.portee !== 'tous';
			return '<label class="g-case g-case--detail"><input type="radio" name="portee" value="' + o[0] + '"' + (portee === o[0] ? ' checked' : '') + (interdit ? ' disabled' : '') + '><span><strong>' + esc(o[1]) + '</strong><small>' + esc(o[2]) + (interdit ? ' — au-delà de votre portée.' : '') + '</small></span></label>';
		}).join('');
		var etabs = d.etablissements.map(function (e) {
			var coche = x && x.etablissements.indexOf(e.id) > -1;
			return '<label class="g-case"><input type="checkbox" name="etab" value="' + e.id + '"' + (coche ? ' checked' : '') + (dansMaPortee(d, e.id) ? '' : ' disabled') + '> ' + esc(e.sigle || e.nom) + '</label>';
		}).join('');
		contenu.innerHTML = entete(x ? x.nom : 'Nouveau rôle', 'Cochez ce que le rôle permet, puis où il s’applique. Les comptes qui le portent reçoivent aussitôt les changements.', '', ['#comptes/roles', 'Rôles']) +
			'<form class="g-form" novalidate>' +
			champTexte('r-nom', 'Nom du rôle *', x && x.nom, ' required', 'Ex. « Webmestre d’établissement », « Chargé de communication ».') +
			'<div class="g-section"><h3>Permissions</h3><p class="g-aide">Chaque permission ouvre une rubrique de la gestion. Tout compte de la gestion peut envoyer des images et des documents.</p></div>' + cases +
			'<div class="g-section"><h3>Portée</h3><p class="g-aide">Elle limite les permissions marquées « limitée à la portée » : page de l’établissement, formations, personnel.</p></div>' +
			'<fieldset class="g-cases g-cases--colonne"><legend class="sr">Portée</legend>' + radios + '</fieldset>' +
			'<fieldset class="g-cases" id="r-etabs" hidden><legend>Établissements du rôle</legend>' + etabs + '</fieldset>' +
			'<div class="g-form__actions"><a class="g-btn" href="#comptes/roles">Annuler</a><button class="g-btn g-btn--primaire" type="submit">' + (x ? 'Enregistrer' : 'Créer le rôle') + '</button></div></form>';
		var form = contenu.querySelector('form');
		function majPortee() {
			var v = (form.querySelector('[name=portee]:checked') || {}).value;
			form.querySelector('#r-etabs').hidden = v !== 'plusieurs';
		}
		form.addEventListener('change', majPortee);
		majPortee();
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var valeurs = function (nom) { return [].map.call(form.querySelectorAll('[name=' + nom + ']:checked'), function (i) { return i.value; }); };
			var corps = {
				slug: x ? x.slug : '',
				nom: form.querySelector('#r-nom').value.trim(),
				permissions: valeurs('perm'),
				portee: (form.querySelector('[name=portee]:checked') || {}).value || 'un',
				etablissements: valeurs('etab').map(function (v) { return parseInt(v, 10); })
			};
			var bouton = form.querySelector('[type=submit]');
			bouton.disabled = true;
			api('sueb/v1/roles', { method: 'POST', body: corps }).then(function () {
				notifier(x ? 'Rôle enregistré.' : 'Rôle créé.');
				location.hash = '#comptes/roles';
			}).catch(function (err) {
				notifier(err.message, true);
				bouton.disabled = false;
			});
		});
	}

	/* ---------- Démarrage ---------- */

	monter();
	document.body.classList.add('g-prete');
	document.addEventListener('selectionchange', majOutils);
	window.addEventListener('hashchange', route);
	route();
})();
