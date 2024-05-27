<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Scaffold\Tests;

use AlexSkrypnyk\Customizer\Tests\Dirs;
use AlexSkrypnyk\Customizer\Tests\Functional\CustomizerTestCase;
use AlexSkrypnyk\drupal_extension_scaffold\Scaffold\CustomizeCommand;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\Finder\Finder;

/**
 * Test Customizer as a dependency during `composer create-project`.
 */
class CreateProjectTest extends CustomizerTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $reflector = new \ReflectionClass(CustomizeCommand::class);
    $this->customizerFile = basename((string) $reflector->getFileName());

    // Initialize the Composer command tester.
    $this->composerCommandInit();

    // Initialize the directories.
    $this->dirsInit(static function (Dirs $dirs): void {
      $finder = new Finder();
      $finder
        ->ignoreDotFiles(FALSE)
        ->ignoreVCS(TRUE)
        ->exclude(['vendor', '.idea'])
        ->in($dirs->root . '/..');
      $dirs->fs->mirror(
        $dirs->root . '/..',
        $dirs->repo,
        $finder->getIterator()
      );
      $dirs->fs->remove($dirs->repo . DIRECTORY_SEPARATOR . '.scaffold' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . CustomizeCommand::CONFIG_FILE);
      $dirs->fs->symlink(
        $dirs->root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . CustomizeCommand::CONFIG_FILE,
        $dirs->repo . DIRECTORY_SEPARATOR . '.scaffold' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . CustomizeCommand::CONFIG_FILE,
        TRUE
      );
    }, (string) getcwd());

    // Update the 'autoload' to include the command file from the project
    // root to get code test coverage.
    $json = $this->composerJsonRead($this->dirs->repo . '/composer.json');
    $json['autoload']['classmap'] = [
      $this->dirs->root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $this->customizerFile,
      $this->dirs->root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Str2Name.php',
    ];
    $this->composerJsonWrite($this->dirs->repo . '/composer.json', $json);

    // Save the package name for later use in tests.
    $this->packageName = $json['name'];

    // Change the current working directory to the 'system under test'.
    chdir($this->dirs->sut);
  }

  #[RunInSeparateProcess]
  public function testCreateProjectNoInstall(): void {
    $this->customizerSetAnswers([
      'testorg/testpackage',
      'Test description',
      'module',
      'GitHub Actions',
      'Ahot',
      self::TUI_ANSWER_NOTHING,
    ]);
    $this->composerCreateProject([
      '--no-install' => TRUE,
      '--repository' => [
        json_encode([
          'type' => 'path',
          'url' => $this->dirs->repo,
          'options' => ['symlink' => TRUE],
        ]),
      ],
    ]);

    $this->assertComposerCommandSuccessOutputContains('Welcome to the Drupal Extension Scaffold project customizer');
    $this->assertComposerCommandSuccessOutputContains('Project was customized');

    $this->assertFilesCommon();
  }

  protected function assertFilesCommon(): void {
    $this->assertDirectoryExists('.devtools');
    $this->assertFileDoesNotExist('.github/FUNDING.yml');
    $this->assertFileDoesNotExist('.github/workflows/scaffold-release.yml');
    $this->assertFileDoesNotExist('.github/workflows/scaffold-test.yml');
    $this->assertDirectoryDoesNotExist('.scaffold');
    $this->assertDirectoryDoesNotExist('build');
    $this->assertDirectoryExists('config');
    $this->assertDirectoryExists('src');
    $this->assertDirectoryExists('tests');
    $this->assertDirectoryDoesNotExist('vendor');
    $this->assertFileExists('.editorconfig');
    $this->assertFileExists('.gitattributes');
    $this->assertFileExists('.gitignore');
    $this->assertFileExists('.twig-cs-fixer.php');
    $this->assertFileExists('composer.dev.json');
    $this->assertFileExists('composer.json');
    $this->assertFileDoesNotExist('composer.lock');
    $this->assertFileDoesNotExist('LICENSE');
    $this->assertFileExists('phpcs.xml');
    $this->assertFileExists('phpmd.xml');
    $this->assertFileExists('phpstan.neon');
    $this->assertFileExists('phpunit.xml');
    $this->assertFileDoesNotExist('README.dist.md');
    $this->assertFileExists('README.md');
    $this->assertFileExists('rector.php');
    $this->assertFileExists('renovate.json');
  }

}
