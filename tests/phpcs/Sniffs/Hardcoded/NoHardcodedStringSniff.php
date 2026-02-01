<?php

namespace Tests\PHPCS\Sniffs\Hardcoded;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class NoHardcodedStringSniff implements Sniff
{
    public function register()
    {
        return [T_CONSTANT_ENCAPSED_STRING];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $token = $tokens[$stackPtr];
        $content = $token['content'];

        // Ignore short strings (likely config keys or codes)
        if (strlen($content) < 5) {
            return;
        }

        // Check for translation function usage before the string
        $prevTokenIndex = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if ($prevTokenIndex !== false) {
            $prevToken = $tokens[$prevTokenIndex];
            
            // If strictly inside __() or trans(), we might see ( before string.
            // But we want to flag strings that are NOT arguments to these.
            // Actually, simply checking if we are arguments to specific functions is complex in regex/simple loop.
            // Heuristic: If previous token is '(', check what came BEFORE '('.
            if ($prevToken['code'] === T_OPEN_PARENTHESIS) {
                $funcNameIndex = $phpcsFile->findPrevious(T_WHITESPACE, $prevTokenIndex - 1, null, true);
                if ($funcNameIndex !== false) {
                    $funcNameToken = $tokens[$funcNameIndex];
                    if ($funcNameToken['code'] === T_STRING) {
                        $funcName = $funcNameToken['content'];
                        if (in_array($funcName, ['__', 'trans', 'lang', 'config', 'env', 'view', 'route'])) {
                            return; // Wrapped in valid function
                        }
                    }
                }
            }
            
             // Ignore if it's an array key:  'key' => 'value'
             // If this token is 'key', the next non-whitespace char is =>
             $nextTokenIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
             if ($nextTokenIndex !== false && $tokens[$nextTokenIndex]['code'] === T_DOUBLE_ARROW) {
                 return; 
             }
             
             // Ignore 'use' statements, namespace, etc. handled by other sniffs usually? 
             // PHPCS tokenizing handles those as T_STRING often, but "use 'string'" isn't valid PHP. 
             // "use Namespace;" uses T_STRING.
        }

        $phpcsFile->addWarning(
            'Potential hardcoded string detected: %s. Consider using translation: __(\'key\')',
            $stackPtr,
            'Found',
            [$content]
        );
    }
}
