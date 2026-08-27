<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeployArtifactsTest extends TestCase
{
    private function projectRoot(): string
    {
        // tests/Feature → tests → backend → code → project
        return dirname(__DIR__, 4);
    }

    public function test_workflows_ci_e_deploy_self_hosted_existem(): void
    {
        $root = $this->projectRoot();
        $ci = $root.DIRECTORY_SEPARATOR.'.github'.DIRECTORY_SEPARATOR.'workflows'.DIRECTORY_SEPARATOR.'ci.yml';
        $deploy = $root.DIRECTORY_SEPARATOR.'.github'.DIRECTORY_SEPARATOR.'workflows'.DIRECTORY_SEPARATOR.'deploy.yml';

        $this->assertFileExists($ci, $ci);
        $this->assertFileExists($deploy, $deploy);

        $ciYaml = (string) file_get_contents($ci);
        $this->assertStringContainsString('vendor/bin/phpunit', $ciYaml);
        $this->assertStringContainsString('code/frontend', $ciYaml);

        $deployYaml = (string) file_get_contents($deploy);
        $this->assertStringContainsString('self-hosted', $deployYaml);
        $this->assertStringContainsString('funeraria-baldan-nf_github', $deployYaml);
        $this->assertStringContainsString('vitaovolt/funeraria-baldan-nf.git', $deployYaml);
        $this->assertStringContainsString('DEPLOY_PATH', $deployYaml);
        $this->assertStringContainsString('funeraria-baldan-nf-queue', $deployYaml);
        $this->assertStringNotContainsString('SSH_PRIVATE_KEY', $deployYaml);
    }

    public function test_docs_deploy_e_queue_worker_existem(): void
    {
        $root = $this->projectRoot();
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'DEPLOY.md');
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'queue-worker.service.example');
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'nginx-spa-api.conf.example');

        $deployDoc = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'DEPLOY.md');
        $this->assertStringContainsString('DEPLOY_PATH', $deployDoc);
        $this->assertStringContainsString('funeraria-baldan-nf_github', $deployDoc);
        $this->assertStringContainsString('/api/v1/health', $deployDoc);
    }

    public function test_health_ainda_expoe_checks_database(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('data.service', 'funeraria-baldan-nf-api')
            ->assertJsonPath('data.checks.database', 'ok');
    }
}
