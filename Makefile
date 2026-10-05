include ../../PluginsMakefile.mk

##—— Centreon local test environment (WireMock API) ————————————————————————————
# See plugins/centreon/.dev/README.md. These targets drive docker compose from
# the GLPI root, layering plugins/centreon/.dev/docker-compose.centreon.yaml on
# top of core. A full Centreon stack is not reproducible, so the plugin's HTTP
# calls are served from canned JSON fixtures under .dev/wiremock/.

CENTREON_COMPOSE_FILES = -f docker-compose.yaml -f plugins/centreon/.dev/docker-compose.centreon.yaml

centreon-env-up: ## Start the mock Centreon API (WireMock) alongside the running dev stack
	@$(COMPOSE) ps --status running --services 2>/dev/null | grep -qx app \
		|| { echo "The 'app' container is not running - start the dev container first."; exit 1; }
	cd $(GLPI_DIR) && $(COMPOSE) $(CENTREON_COMPOSE_FILES) up -d --no-recreate centreon-api
.PHONY: centreon-env-up

centreon-env-down: ## Stop and remove the mock Centreon API container (stateless, no volume)
	cd $(GLPI_DIR) && $(COMPOSE) $(CENTREON_COMPOSE_FILES) rm -sf centreon-api
.PHONY: centreon-env-down

centreon-verify: ## Check the mock API answers a login request from inside the app container
	@$(COMPOSE) exec -T app sh -c 'curl -sf -X POST http://centreon-api:8080/centreon/api/latest/login \
		-H "Content-Type: application/json" -d "{}" | grep -q dev-fixture-token' \
		&& echo "mock Centreon API OK" || { echo "mock Centreon API NOT reachable"; exit 1; }
.PHONY: centreon-verify
