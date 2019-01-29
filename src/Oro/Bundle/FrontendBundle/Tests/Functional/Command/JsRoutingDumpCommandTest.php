<?php

namespace Oro\Bundle\FrontendBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class JsRoutingDumpCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testExecute(): void
    {
        $result = $this->runCommand('fos:js-routing:dump', ['-vvv']);

        $this->assertNotEmpty($result);
        $this->assertContains($this->getEndPath('routes', 'json'), $result);
        $this->assertContains($this->getEndPath('frontend_routes', 'json'), $result);
    }

    public function testExecuteWithJsFormat(): void
    {
        $result = $this->runCommand('fos:js-routing:dump', ['-vvv', '--format=js']);

        $this->assertNotEmpty($result);
        $this->assertContains($this->getEndPath('routes', 'js'), $result);
        $this->assertContains($this->getEndPath('frontend_routes', 'js'), $result);
    }

    public function testExecuteWithCustomTarget(): void
    {
        $projectDir = $this->getContainer()
            ->getParameter('kernel.project_dir');

        $endPath = $this->getEndPath('custom_routes', 'json');

        $result = $this->runCommand('fos:js-routing:dump', ['-vvv', sprintf('--target=%s%s', $projectDir, $endPath)]);

        $this->assertNotEmpty($result);
        $this->assertContains($endPath, $result);
        $this->assertContains($this->getEndPath('frontend_custom_routes', 'json'), $result);
    }

    /**
     * @param string $filename
     * @param string $format
     * @return string
     */
    private function getEndPath(string $filename, string $format): string
    {
        return implode(DIRECTORY_SEPARATOR, ['', 'public', 'media', 'js', $filename . '.' . $format]);
    }
}
