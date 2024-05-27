<?php

declare(strict_types=1);

use Scaffold\CustomizeCommand;

/**
 * Customizer configuration.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class CustomizerConfig {

  public static function messages(CustomizeCommand $customizer): array {
    return [
      'welcome' => 'Welcome to the Drupal Extension Scaffold project customizer',
    ];
  }

  public static function questions(CustomizeCommand $customizer): array {
    return [
      'Name' => [
        'question' => static fn(array $answers, CustomizeCommand $customizer): mixed => $customizer->io->ask('Package as namespace/name', NULL, static function (string $value): string {
          if (Str2Name::phpPackage($value) !== $value) {
            throw new \InvalidArgumentException(sprintf('The package name "%s" is invalid, it should be lowercase and have a vendor name, a forward slash, and a package name.', $value));
          }

          return $value;
        }),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $customizer): void {
          $package_name = $customizer->packageData['name'];

          $cwd = $customizer->cwd;

          [$old_namespace, $old_name] = explode('/', $package_name);
          [$new_namespace, $new_name] = explode('/', $answer);

          $old_title = Str2Name::label($old_name);
          $new_title = Str2Name::label($new_name);
          $customizer->replaceInPath($cwd, Str2Name::phpPackageNamespace($old_namespace), Str2Name::phpPackageNamespace($new_namespace));
          $customizer->replaceInPath($cwd, Str2Name::phpNamespace($old_namespace), Str2Name::phpNamespace($new_namespace));
          $customizer->replaceInPath($cwd, Str2Name::phpPackageName($old_name), Str2Name::phpPackageName($new_name));
          $customizer->replaceInPath($cwd, Str2Name::phpClass($old_name), Str2Name::phpClass($new_name));

          $customizer->replaceInPath($cwd, $old_title, $new_title);
          //Replace for URL.
          //$customizer->replaceInPath($cwd, Str2Name::
        },
      ],
      'Description' => [
        'question' => static fn(array $answers, CustomizeCommand $customizer): mixed => $customizer->io->ask('Description'),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $customizer): void {
          $description = $customizer->packageData['description'];
          $customizer->packageData['description'] = $answer;
          $customizer->writeComposerJson($customizer->packageData);
          $customizer->replaceInPath($customizer->cwd, $description, $answer);
        },
      ],
      'Type' => [
        'question' => static fn(array $answers, CustomizeCommand $customizer): mixed => $customizer->io->choice('License type', [
          'module',
          'theme',
        ], 'module'),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $customizer): void {
          if ($answer === 'module') {
            $customizer->fs->remove($customizer->cwd . '/package.json');
            $customizer->fs->remove($customizer->cwd . '/package-lock.json');
          }
          else {
            $contents = file_get_contents($customizer->cwd . DIRECTORY_SEPARATOR . $customizer->packageData['name'] . '.info.yml');
            $contents .= "\n";
            $contents .= 'base theme: false';
            file_put_contents($customizer->cwd . DIRECTORY_SEPARATOR . $customizer->packageData['name'] . '.info.yml', $contents);
          }
        },
      ],
      'CI Provider' => [
        'question' => static fn(array $answers, CustomizeCommand $customizer): mixed => $customizer->io->choice('CI Provider', [
          'GitHub Actions',
          'CircleCI',
        ], 'GitHub Actions'),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $customizer): void {
          if ($answer === 'GitHub Actions' || $answer === 'None') {
            $customizer->fs->removeDirectory($customizer->cwd . '/.circleci');
          }
          if ($answer === 'GitHub Actions' || $answer === 'None') {
            $customizer->fs->remove($customizer->cwd . '/.github/test.yml');
            $customizer->fs->remove($customizer->cwd . '/.github/deploy.yml');
          }
        },
      ],
      'Command wrapper' => [
        'question' => static fn(array $answers, CustomizeCommand $customizer): mixed => $customizer->io->choice('Command wrapper', [
          'Ahoy',
          'Makefile',
          'None',
        ], 'Ahoy'),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $customizer): void {
          if ($answer === 'Makefile' || $answer === 'None') {
            $customizer->fs->remove($customizer->cwd . '/ahoy.yml');
          }
          if ($answer === 'Ahoy' || $answer === 'None') {
            $customizer->fs->remove($customizer->cwd . '/Makefile');
          }
        },
      ],
    ];
  }

  public static function cleanup(array &$composerjson, CustomizeCommand $customizer): void {
    // replace_string_content "YourNamespace" "${extension_machine_name}"
    // replace_string_content "yournamespace" "${extension_machine_name}"
    // replace_string_content "AlexSkrypnyk" "${extension_machine_name}"
    // replace_string_content "alexskrypnyk" "${extension_machine_name}"
    // replace_string_content "yourproject" "${extension_machine_name}"
    // replace_string_content "Yourproject logo" "${extension_name} logo"
    // replace_string_content "Your Extension" "${extension_name}"
    // replace_string_content "your extension" "${extension_name}"
    // replace_string_content "Your+Extension" "${extension_machine_name}"
    // replace_string_content "your_extension" "${extension_machine_name}"
    // replace_string_content "YourExtension" "${extension_machine_name_class}"
    // replace_string_content "Provides your_extension functionality." "Provides ${extension_machine_name} functionality."
    // replace_string_content "drupal-module" "drupal-${extension_type}"
    // replace_string_content "Drupal module scaffold FE example used for template testing" "Provides ${extension_machine_name} functionality."
    // replace_string_content "Drupal extension scaffold" "${extension_name}"
    // replace_string_content "drupal_extension_scaffold" "${extension_machine_name}"
    // replace_string_content "type: module" "type: ${extension_type}"
    // replace_string_content "\[EXTENSION_NAME\]" "${extension_machine_name}"
    //
    // remove_string_content "# Uncomment the lines below in your project."
    // uncomment_line ".gitattributes" ".ahoy.yml"
    // uncomment_line ".gitattributes" ".circleci"
    // uncomment_line ".gitattributes" ".devtools"
    // uncomment_line ".gitattributes" ".editorconfig"
    // uncomment_line ".gitattributes" ".gitattributes"
    // uncomment_line ".gitattributes" ".github"
    // uncomment_line ".gitattributes" ".gitignore"
    // uncomment_line ".gitattributes" ".twig-cs-fixer.php"
    // uncomment_line ".gitattributes" "Makefile"
    // uncomment_line ".gitattributes" "composer.dev.json"
    // uncomment_line ".gitattributes" "phpcs.xml"
    // uncomment_line ".gitattributes" "phpmd.xml"
    // uncomment_line ".gitattributes" "phpstan.neon"
    // uncomment_line ".gitattributes" "rector.php"
    // uncomment_line ".gitattributes" "renovate.json"
    // uncomment_line ".gitattributes" "tests"
    // remove_string_content "# Remove the lines below in your project."
    // remove_string_content ".github\/FUNDING.yml export-ignore"
    // remove_string_content "LICENSE             export-ignore"
    //
    // mv "your_extension.info.yml" "${extension_machine_name}.info.yml"
    // mv "your_extension.install" "${extension_machine_name}.install"
    // mv "your_extension.links.menu.yml" "${extension_machine_name}.links.menu.yml"
    // mv "your_extension.module" "${extension_machine_name}.module"
    // mv "your_extension.routing.yml" "${extension_machine_name}.routing.yml"
    // mv "your_extension.services.yml" "${extension_machine_name}.services.yml"
    // mv "config/schema/your_extension.schema.yml" "config/schema/${extension_machine_name}.schema.yml"
    // mv "src/Form/YourExtensionForm.php" "src/Form/${extension_machine_name_class}Form.php"
    // mv "src/YourExtensionService.php" "src/${extension_machine_name_class}Service.php"
    // mv "tests/src/Unit/YourExtensionServiceUnitTest.php" "tests/src/Unit/${extension_machine_name_class}ServiceUnitTest.php"
    // mv "tests/src/Kernel/YourExtensionServiceKernelTest.php" "tests/src/Kernel/${extension_machine_name_class}ServiceKernelTest.php"
    // mv "tests/src/Functional/YourExtensionFunctionalTest.php" "tests/src/Functional/${extension_machine_name_class}FunctionalTest.php"
    //
    // remove_tokens_with_content "META"
    // remove_special_comments

    $customizer->fs->remove($customizer->cwd . 'LICENSE');
    $customizer->fs->remove($customizer->cwd . '.github/workflows/scaffold*.yml');
    $customizer->fs->remove($customizer->cwd . '.scalfold');
  }

}
