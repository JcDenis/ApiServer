# ApiServer

Notes for AI assistants working on the Dotclear plugin ApiServer.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Serv your Dotclear blog through API.

No admin page. Public side: behaviors.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/ApiServer.php`
* `src/ApiServerCache.php`: core cache.
* `src/ApiServerEndpoint.php`: core endpoint.
* `src/ApiServerException.php`: core exception.
* `src/ApiServerLifetime.php`: core timestamp helper.
* `src/ApiServerLogs.php`: core logs helper.
* `src/ApiServerRate.php`: core rate limit.
* `src/ApiServerResponse.php`: core reponse.
* `src/ApiServerToken.php`: core token.
* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendBehaviors.php`: admin behaviors.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/Install.php`: install and update, default settings.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Prepend.php`: loaded on every request: autoload, URL handlers, permission types.
* `src/Endpoint/AuthEndpoint.php`: authentication API endpoint.
* `src/Endpoint/BlogEndpoint.php`: for now, this endpoint experiment anonymous query.
* `src/Endpoint/CodesEndpoint.php`: user API endpoint.
* `src/Endpoint/EndpointsEndpoint.php`: user API endpoint.
* `src/Endpoint/UserEndpoint.php`: user API endpoint.
* `src/MaintenanceTask/CleanCache.php`: module maintenance task to clean cache.
* Behaviors used: `adminAfterUserCreate`, `adminAfterUserUpdate`, `adminUserForm`, `adminBlogPreferencesFormV2`, `adminBeforeBlogSettingsUpdate`, `adminDashboardOptionsForm`, `adminAfterDashboardOptionsUpdate`, `adminDashboardContentsV2`.

## Pitfalls

* Other modules talk through it: DotclearWatch, DotclearWatchServer, daContribute and ClaudetteMcp (raw endpoint). A change of its endpoints or answers must keep them working.
* Data migrations example: user options moved to user preferences in 0.13, see `src/Install.php`.

## Testing

* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
