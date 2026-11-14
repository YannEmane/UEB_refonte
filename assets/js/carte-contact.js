/* Page Contact : la carte des sites de l'université (Leaflet, fonds OpenStreetMap).
   Un repère numéroté par site ; sa bulle liste ce qui s'y trouve. Le bouton d'un site,
   dans la liste, centre la carte sur lui et ouvre sa bulle. */
(function () {
	var boite = document.getElementById('carte-sites');
	if (!boite || typeof L === 'undefined') {
		if (boite) boite.querySelector('.sites__repli').textContent = 'La carte n’a pas pu être chargée. Les sites et leurs contacts sont listés ci-contre.';
		return;
	}
	var sites = JSON.parse(boite.getAttribute('data-sites') || '[]');
	if (!sites.length) return;
	boite.innerHTML = '';

	var carte = L.map(boite, { scrollWheelZoom: false, zoomControl: true });
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 18,
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
	}).addTo(carte);

	function esc(t) {
		return String(t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
	}

	var reperes = sites.map(function (s, k) {
		var icone = L.divIcon({ className: 'repere', html: '<span>' + (k + 1) + '</span>', iconSize: [36, 44], iconAnchor: [18, 42], popupAnchor: [0, -38] });
		var itineraire = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(s.lat + ',' + s.lon);
		return L.marker([s.lat, s.lon], { icon: icone, title: s.lieu, alt: s.lieu })
			.addTo(carte)
			.bindPopup('<strong class="bulle__lieu">' + esc(s.lieu) + '</strong><span class="bulle__noms">' + s.noms.map(esc).join(' · ') +
				'</span><a class="bulle__lien" href="#site-' + k + '">Voir les contacts</a> <a class="bulle__lien" href="' + itineraire + '" target="_blank" rel="noopener">Itinéraire</a>');
	});

	var cadre = L.latLngBounds(sites.map(function (s) { return [s.lat, s.lon]; }));
	if (sites.length > 1) carte.fitBounds(cadre, { padding: [40, 40] });
	else carte.setView([sites[0].lat, sites[0].lon], 13);

	/* La molette ne zoome qu'après un clic dans la carte : le défilement de la page reste libre. */
	carte.on('click', function () { carte.scrollWheelZoom.enable(); });
	carte.on('mouseout', function () { carte.scrollWheelZoom.disable(); });

	/* Repère ouvert : le site correspondant est mis en évidence dans la liste. */
	reperes.forEach(function (r, k) {
		r.on('popupopen', function () {
			[].forEach.call(document.querySelectorAll('.site'), function (el) { el.classList.toggle('est-actif', el.getAttribute('data-site') === String(k)); });
		});
	});

	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-voir-site]');
		if (!b) return;
		var k = parseInt(b.getAttribute('data-voir-site'), 10);
		var s = sites[k];
		if (!s) return;
		if (window.matchMedia('(max-width: 980px)').matches) boite.scrollIntoView({ behavior: 'smooth', block: 'center' });
		carte.flyTo([s.lat, s.lon], 13, { duration: .8 });
		carte.once('moveend', function () { reperes[k].openPopup(); });
	});
})();
