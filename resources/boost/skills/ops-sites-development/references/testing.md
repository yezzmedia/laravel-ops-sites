# Ops Sites Testing Pattern

- Keep registration expectations in `RegistrationTest`.
- Keep install and store behavior in `StoreAndInstallTest`.
- Keep mutation and page/detail workflows in `OpsSitesPageTest` and `PluginAndDetailsTest`.
- Keep posture refresh and audit behavior in their dedicated feature tests.
- Run `composer test:ops-sites` from `/home/yezz/Developement/packages/1-dev-test` when available in the shared runner.
