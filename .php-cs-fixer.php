<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require_once __DIR__ . '/.dev-tools/vendor/kubawerlos/php-cs-fixer-config/src/Rules/RulesInterface.php';
require_once __DIR__ . '/.dev-tools/vendor/kubawerlos/php-cs-fixer-config/src/Rules/LibraryRules.php';
$rules = (new PhpCsFixerConfig\Rules\LibraryRules('PHP CS Fixer: custom fixers', 'Kuba Werłos', 2018))->getRules();

// add new fixers that are not in PhpCsFixerConfig yet
foreach (new PhpCsFixerCustomFixers\Fixers() as $fixer) {
    if ($fixer instanceof \PhpCsFixer\Fixer\DeprecatedFixerInterface) {
        continue;
    }
    if (!isset($rules[$fixer->getName()])) {
        $rules[$fixer->getName()] = true;
    }
}

// PhpCsFixerCustomFixersDev\Fixer\OrderedClassElementsInternalFixer will handle this
unset($rules['ordered_class_elements']);
unset($rules['ordered_interfaces']);

unset($rules['assign_null_coalescing_to_coalesce_equal']); // TODO: remove when dropping support to PHP <8.0
unset($rules['modernize_strpos']); // TODO: remove when dropping support to PHP <8.0
unset($rules['use_arrow_functions']); // TODO: remove when dropping support to PHP <7.4
unset($rules[PhpCsFixerCustomFixers\Fixer\PromotedConstructorPropertyFixer::name()]); // TODO: remove when dropping support to PHP <8.0
$rules['trailing_comma_in_multiline'] = true; // TODO: remove when dropping support to PHP <8.0

foreach (new PhpCsFixerCustomFixersDev\Fixers() as $fixer) {
    $rules[$fixer->getName()] = true;
}

return (new PhpCsFixer\Config())
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->registerCustomFixers(new PhpCsFixerCustomFixersDev\Fixers())
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->files()
            ->in(__DIR__ . '/.dev-tools/src')
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
            ->notName('php-cs-fixer.config.*.php')
            ->append([
                __DIR__ . '/.dev-tools/build-infection-config',
                __DIR__ . '/.dev-tools/readme',
                __FILE__,
            ])
    )
    ->setRules($rules);
