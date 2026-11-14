# Dotclear public API : guide d'utilisation

Ce guide explique comment activer et utiliser l'API publique de votre blog avec le plugin _ApiServer_.

Ce plugin n'a pas de page d'administration. Ses réglages sont dans les préférences du blog et des utilisateurs.

## Sommaire

* [À quoi sert le plugin](#à-quoi-sert-le-plugin)
* [Activer l'API](#activer-lapi)
* [Régler les durées](#régler-les-durées)
* [Limiter le nombre d'appels](#limiter-le-nombre-dappels)
* [Se connecter à l'API](#se-connecter-à-lapi)
* [Les points d'entrée](#les-points-dentrée)
* [Les codes de réponse](#les-codes-de-réponse)
* [Voir les statistiques](#voir-les-statistiques)
* [Vider le cache](#vider-le-cache)
* [Régler les constantes du fichier de configuration](#régler-les-constantes-du-fichier-de-configuration)

## À quoi sert le plugin

_ApiServer_ ajoute une API à votre blog. Une API est une adresse que d'autres programmes peuvent appeler.

Le plugin fournit le moteur : jetons de connexion, limite d'appels, cache et statistiques.

Il ne sert presque aucun point d'entrée utile par lui-même. Les autres plugins ajoutent les leurs.

Les adresses de l'API sont de la forme `https://votre-blog/index.php/api/<point-d-entrée>`.

## Activer l'API

1. Allez dans _Blog_ puis _Paramètres du blog_.
2. Ouvrez la section _Paramètres des plugins pour ce blog_.
3. Cochez _Activer l'API public pour ce blog_.
4. Enregistrez.

![Réglages de l'API dans les paramètres du blog](img/reglages-blog.png)

Tant que la case n'est pas cochée, l'API répond par le code 101 (« Le service est fermé pour maintenance »).

L'option _Ajouter les permissions utilisateur lors de l'inscription_ donne la permission « Client de l'API » aux personnes qui s'inscrivent. Elle sert avec le plugin _FrontendSession_.

L'option _Réinitialiser les appels anonymes à l'API_ n'apparaît que si des appels anonymes ont eu lieu. Elle indique combien d'appels restent. Cochez-la puis enregistrez pour remettre le compteur à zéro.

## Régler les durées

Trois listes se trouvent dans le même cadre.

* _Durée de vie du jeton de l'utilisateur_ : combien de temps un jeton de connexion reste valable (une minute à un mois).
* _Période de remise à zéro de la limite d'appels à l'API_ : la fenêtre de temps sur laquelle les appels sont comptés (une minute à un jour).
* _Durée de vie du cache de l'API_ : combien de temps une réponse reste en cache (une minute à un jour).

La valeur par défaut est d'une heure pour le jeton et la remise à zéro.

Une liste est masquée quand la constante correspondante est définie dans le fichier de configuration (voir la dernière section).

## Limiter le nombre d'appels

Chaque personne a une limite d'appels sur la période de remise à zéro. Les appels sans connexion ont leur propre limite.

Pour changer la limite d'un utilisateur :

1. Allez dans _Système_ puis _Utilisateurs_.
2. Ouvrez la fiche de la personne.
3. Remplissez _Limite de nombre d'appel à l'API par … secondes_ (entre 10 et 9999).
4. Saisissez votre mot de passe puis enregistrez.

![Limite d'appels dans la fiche d'un utilisateur](img/utilisateur-limite.png)

Si le champ est vide ou à 0, la limite par défaut s'applique (3600 appels).

Quand la limite est atteinte, l'API répond par le code 429 (« Limite de quota de l'API atteinte. ») jusqu'à la prochaine remise à zéro.

Chaque réponse contient des en-têtes qui donnent l'état du quota :

* `X-RateLimit-Limit` : la limite ;
* `X-RateLimit-Remaining` : les appels restants ;
* `X-RateLimit-Reset` : le moment de la remise à zéro ;
* `X-RateLimit-NearLimit` : vaut 1 quand la limite est proche.

## Se connecter à l'API

Les points d'entrée protégés demandent un jeton. Pour en obtenir un, appelez `auth` avec un nom d'utilisateur et un mot de passe.

La personne doit avoir la permission « Client de l'API » sur le blog. Un super administrateur l'a toujours.

```
curl -X POST https://votre-blog/index.php/api/auth \
  -d "username=claire" -d "password=son-mot-de-passe"
```

La réponse contient le jeton, sa date de remise à zéro et le quota :

```json
{
  "code": 200,
  "message": "",
  "content": {
    "name": "Claire Martin",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOi…",
    "token_reset": 1794706005,
    "rate_limit": 3600,
    "rate_remain": 3599,
    "rate_reset": 1794706005
  },
  "cache": "0"
}
```

Envoyez ensuite ce jeton dans l'en-tête `Authorization` de chaque appel :

```
curl https://votre-blog/index.php/api/user \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOi…"
```

Quand le jeton expire, appelez `auth` de nouveau.

Un mauvais mot de passe, une personne sans permission ou un jeton invalide donnent le code 109 (« Non autorisé »).

## Les points d'entrée

Le plugin en fournit cinq :

* `blog` : informations publiques du blog (nom, adresse, description, date de mise à jour, nombre d'entrées). Aucune connexion n'est nécessaire. La réponse est mise en cache.
* `auth` : donne un jeton (voir plus haut).
* `user` : donne le nom, le jeton et le quota de la personne connectée.
* `endpoints` : liste tous les points d'entrée et les champs qu'ils attendent. Connexion nécessaire.
* `codes` : liste les codes de réponse et leur texte. Avec le champ `code` en POST, donne le texte d'un seul code. Connexion nécessaire.

Exemple avec `blog` :

```json
{
  "code": 200,
  "message": "",
  "content": {
    "name": "Mon blog",
    "url": "http://127.0.0.1:8080/index.php/",
    "description": "",
    "update": "2026-11-15 00:22:05",
    "nb_posts": 6
  },
  "cache": "1"
}
```

Les autres plugins ajoutent leurs propres points d'entrée à cette liste. Appelez `endpoints` pour voir ceux de votre blog.

Pour choisir une version de l'API, envoyez l'en-tête `X-Api-Version`. La version par défaut est `v1`.

## Les codes de réponse

Toutes les réponses ont la même forme : `code`, `message`, `content` et `cache`.

| Code | Sens |
|------|------|
| 100 | Erreur inconnue |
| 101 | Service fermé : l'API n'est pas activée sur ce blog |
| 102 | Méthode non autorisée : le point d'entrée n'existe pas |
| 103 | Version de l'API non supportée |
| 104 | Paramètre manquant |
| 105 | Pas de contenu |
| 106 | Non trouvé |
| 107 | Contributions fermées |
| 108 | Échec de la soumission |
| 109 | Non autorisé |
| 200 | Ok |
| 429 | Limite de quota de l'API atteinte |

Attention : le statut HTTP de la réponse reste 200 même pour une erreur. Lisez toujours le champ `code`.

## Voir les statistiques

Le plugin peut compter les appels de chaque point d'entrée et les montrer sur votre tableau de bord.

1. Allez dans _Mes préférences_ puis _Mon tableau de bord_.
2. Cochez _Afficher les statistiques de l'API sur le tableau de bord_.
3. Enregistrez les options du tableau de bord.

![Option des statistiques dans les préférences](img/preferences-tableau-de-bord.png)

Un cadre apparaît ensuite sur la page d'accueil de l'administration. Il liste les points d'entrée appelés depuis la dernière remise à zéro.

![Statistiques de l'API sur le tableau de bord](img/tableau-de-bord-statistiques.png)

Le bouton _Effacer les statistiques_ remet les compteurs à zéro.

## Vider le cache

Les réponses mises en cache sont rangées dans le dossier de cache de Dotclear.

Pour les supprimer, allez dans _Système_ puis _Maintenance_ et lancez la tâche _Vider le répertoire de cache de l'API_.

## Régler les constantes du fichier de configuration

Vous pouvez fixer les durées dans le fichier de configuration de Dotclear (`config.php`). La liste correspondante disparaît alors des paramètres du blog.

* `APISERVER_DEFAULT_TOKEN_LIFETIME` : durée de vie du jeton, en secondes.
* `APISERVER_DEFAULT_RATE_LIFETIME` : période de remise à zéro, en secondes.
* `API_SERVER_DEFAULT_CACHE_LIFETIME` : durée de vie du cache, en secondes.
* `API_SERVER_DEFAULT_CALLS_LIMIT` : limite d'appels par défaut.

Les noms des constantes ne suivent pas tous la même écriture : recopiez-les exactement.
