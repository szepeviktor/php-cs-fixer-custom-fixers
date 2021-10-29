<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoUselessCommentFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no useless comments.',
            [
                new CodeSample('<?php
/**
 * Class Foo
 * Class to do something
 */
class Foo {
    /**
     * Get bar
     */
    function getBar() {}
}
'),
            ]
        );
    }

    /**
     * Must run before NoEmptyCommentFixer, NoEmptyPhpdocFixer, PhpdocTrimConsecutiveBlankLineSeparationFixer, PhpdocTrimFixer.
     */
    public function getPriority(): int
    {
        return 4;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([\T_COMMENT, \T_DOC_COMMENT]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind([\T_COMMENT, \T_DOC_COMMENT])) {
                continue;
            }

            $newContent = $this->getNewContent($tokens, $index);

            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([$tokens[$index]->getId(), $newContent]);
        }
    }

    private function getNewContent(Tokens $tokens, int $index): string
    {
        $content = $tokens[$index]->getContent();

        $nextIndex = $tokens->getTokenNotOfKindSibling(
            $index,
            1,
            [[\T_WHITESPACE], [\T_COMMENT], [\T_ABSTRACT], [\T_FINAL], [\T_PUBLIC], [\T_PROTECTED], [\T_PRIVATE], [\T_STATIC]]
        );

        if ($nextIndex === null) {
            return $content;
        }

        if ($tokens[$nextIndex]->isGivenKind([\T_CLASS, \T_INTERFACE, \T_TRAIT])) {
            /** @var int $classyNameIndex */
            $classyNameIndex = $tokens->getNextMeaningfulToken($nextIndex);

            /** @var string $content */
            $content = Preg::replace(
                \sprintf('~
                        \R?
                        (?<=\n|\r|\r\n|^\#|^/{2}|^/\*[^\*\s]|^/\*{2})
                        \h*\**\h*
                        (
                            (class|interface|trait)\h+([a-zA-Z\d\\\\]+)
                            |
                            %s
                        )
                        \.?
                        \h*
                        (?=\R|\*/$|$)
                    ~ix', $tokens[$classyNameIndex]->getContent()),
                '',
                $content
            );
        } elseif ($tokens[$nextIndex]->isGivenKind(\T_FUNCTION)) {
            /** @var string $content */
            $content = Preg::replace(
                '/\R?(?<=\n|\r|\r\n|^#|^\/\/|^\/\*|^\/\*\*)\h+\**\h*((adds?|gets?|removes?|sets?)\h+[A-Za-z0-9\\\\_]+|([A-Za-z0-9\\\\_]+\h+)?constructor).?(?=\R|$)/i',
                '',
                $content
            );
        } else {
            return $content;
        }

        if ($content === '/***/') {
            $content = '/** */';
        }

        return $content;
    }
}
