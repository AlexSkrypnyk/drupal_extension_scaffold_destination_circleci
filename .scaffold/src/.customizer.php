<?php

declare(strict_types=1);

use Scaffold\CustomizeCommand;

/**
 * Customizer configuration.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class CustomizerConfig {

  public static function questions(CustomizeCommand $customizer): array {
    // This an example of questions that can be asked to customize the project.
    // You can adjust this method to ask questions that are relevant to your
    // project.
    //
    // In this example, we ask for the package name, description, and license.
    //
    // You may remove all the questions below and replace them with your own.
    return [
      'Name' => [
        // The question callback function defines how the question is asked.
        // In this case, we ask the user to provide a package name as a string.
        'question' => static fn(array $answers, CustomizeCommand $customizer): mixed => $customizer->io->ask('Package name', NULL, static function (string $value): string {
          // This is a validation callback that checks if the package name is
          // valid. If not, an exception is thrown with a message shown to the
          // user.
          if (!preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/', $value)) {
            throw new \InvalidArgumentException(sprintf('The package name "%s" is invalid, it should be lowercase and have a vendor name, a forward slash, and a package name.', $value));
          }

          return $value;
        }),
        // The process callback function defines how the answer is processed.
        // The processing takes place only after all answers are received and
        // the user confirms the intended changes.
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $customizer): void {
          $name = $customizer->packageData['name'];
          // Update the package data.
          $customizer->packageData['name'] = $answer;
          // Write the updated composer.json file.
          $customizer->writeComposerJson($customizer->packageData);
          // Replace the package name in the project files.
          $customizer->replaceInPath($customizer->cwd, $name, $answer);
        },
      ],
      'Description' => [
        // For this question, we are using an answer from the previous question
        // in the title of the question.
        'question' => static fn(array $answers, CustomizeCommand $customizer): mixed => $customizer->io->ask(sprintf('Description for %s', $answers['Name'])),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $customizer): void {
          $description = $customizer->packageData['description'];
          $customizer->packageData['description'] = $answer;
          $customizer->writeComposerJson($customizer->packageData);
          $customizer->replaceInPath($customizer->cwd, $description, $answer);
        },
      ],
      'License' => [
        // For this question, we are using a pre-defined list of options.
        // For processing, we are using a separate method named 'processLicense'
        // (only for the demonstration purposes; it could have been an
        // anonymous function).
        'question' => static fn(array $answers, CustomizeCommand $customizer): mixed => $customizer->io->choice('License type', [
          'MIT',
          'GPL-3.0-or-later',
          'Apache-2.0',
        ], 'GPL-3.0-or-later'),
        'process' =>   static function (string $title, string $answer, array $answers, CustomizeCommand $customizer): void {
          $customizer->packageData['license'] = $answer;
          $customizer->writeComposerJson($customizer->packageData);
        }
      ],
    ];
  }

  public static function cleanup(array &$composerjson, CustomizeCommand $customizer): void {
    // Here you can remove any sections from the composer.json file that are not
    // needed for the project before all dependencies are updated.
    //
    // You can also additionally process files.
    //
    // We are removing the `require-dev` section and the `autoload-dev` section
    // from the composer.json file used for tests.
    CustomizeCommand::arrayUnsetDeep($composerjson, ['require-dev', 'composer/composer']);
    CustomizeCommand::arrayUnsetDeep($composerjson, ['require-dev', 'phpunit/phpunit']);
    CustomizeCommand::arrayUnsetDeep($composerjson, ['autoload-dev', 'psr-4', 'AlexSkrypnyk\\TemplateProjectExample\\Tests\\']);

    $customizer->fs->remove($customizer->cwd . '/tests');
  }

}
