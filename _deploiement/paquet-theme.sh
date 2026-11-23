#!/usr/bin/env bash
# Prépare le dossier du thème à envoyer sur OVH (www/site-ueb/wp-content/themes/site-ueb/), à partir du
# dernier commit (pas des fichiers modifiés non commités).
#
# Sont écartés : les dossiers qui commencent par « _ » (_installation : scripts et données de test,
# _remotion : vidéos et node_modules, _deploiement), les .patch, README.md et .gitignore.
#
#   _deploiement/paquet-theme.sh                  crée ../deploiement/theme-<commit>/
#   _deploiement/paquet-theme.sh <dossier>        crée <dossier> (qui ne doit pas exister)
#   _deploiement/paquet-theme.sh <dossier> <ref>  ne garde que les fichiers changés depuis <ref>
#                                                 (ex. origin/main~3) ; la liste des fichiers à supprimer
#                                                 sur le serveur va dans <dossier>.a-supprimer.txt
set -euo pipefail

racine=$(git -C "$(dirname "$0")" rev-parse --show-toplevel)
cd "$racine"
commit=$(git rev-parse --short HEAD)
dest=${1:-"$racine/../deploiement/theme-$commit"}
depuis=${2:-}

if [ -e "$dest" ]; then
	echo "« $dest » existe déjà : choisir un autre dossier." >&2
	exit 1
fi

garder() { grep -vE '^_|\.patch$|^README\.md$|^\.gitignore$' || true; }

if [ -n "$depuis" ]; then
	fichiers=$(git diff --name-only --diff-filter=ACMR "$depuis" HEAD | garder)
	supprimes=$(git diff --name-only --diff-filter=D "$depuis" HEAD | garder)
else
	fichiers=$(git ls-files | garder)
	supprimes=
fi

if [ -z "$fichiers" ] && [ -z "$supprimes" ]; then
	echo "Rien à envoyer." >&2
	exit 0
fi

mkdir -p "$dest"
if [ -n "$fichiers" ]; then
	# shellcheck disable=SC2086
	git archive --format=tar HEAD $fichiers | tar -x -C "$dest"
fi
if [ -n "$supprimes" ]; then
	echo "$supprimes" > "$dest.a-supprimer.txt"
fi

# Garde-fou : rien de ce qui ne doit pas partir.
if (cd "$dest" && find . \( -path './_*' -o -name '*.patch' \) -print -quit) | grep -q .; then
	echo "Erreur : un fichier de développement s'est glissé dans $dest" >&2
	exit 1
fi

echo "Thème ($commit) prêt dans : $dest"
echo "$(find "$dest" -type f | wc -l) fichiers, $(du -sh "$dest" | cut -f1)"
[ -n "$supprimes" ] && echo "Fichiers à supprimer sur le serveur : $dest.a-supprimer.txt"
exit 0
