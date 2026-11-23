# UEB_refonte
Refonte du site web officiel de l’Université d’Ebolowa (UEB) 

## Déploiement

Ne pas envoyer le dépôt tel quel : `_installation/` (scripts, données de test), `_remotion/` et les `.patch` ne doivent pas partir sur le serveur. Préparer le dossier du thème avec :

```sh
_deploiement/paquet-theme.sh                       # tout le thème, dans ../deploiement/theme-<commit>/
_deploiement/paquet-theme.sh <dossier> <ref>       # seulement les fichiers changés depuis <ref>
```

Par sécurité, `_installation/` et `_deploiement/` contiennent un `.htaccess` qui en refuse l'accès par le web.
