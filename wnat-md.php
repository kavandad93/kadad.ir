<?php

/**
 * md.php - A lightweight Markdown parser written in pure PHP
 * 
 * Parses Markdown input from query string and returns raw HTML
 * No external libraries, no frameworks, no extra styling
 */

// Set content type header
header('Content-Type: text/html; charset=UTF-8');

// ----------------------------------------------------------------------
// 1. Input Retrieval and Sanitization
// ----------------------------------------------------------------------

// Check if parse parameter exists
if (!isset($_GET['parse'])) {
    echo 'Error: Missing "parse" parameter. Usage: md.php?parse=your_markdown_text';
    exit;
}

// Get raw input
$rawInput = $_GET['parse'];

// Limit input length (prevent abuse)
$maxLength = 50000; // 50KB max
if (strlen($rawInput) > $maxLength) {
    echo 'Error: Input exceeds maximum length of ' . $maxLength . ' characters.';
    exit;
}

// Basic sanitization - remove null bytes and control characters
$rawInput = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $rawInput);

// ----------------------------------------------------------------------
// 2. Main Parse Function - Orchestrates all parsing steps
// ----------------------------------------------------------------------

function parseMarkdown($text) {
    // Step 1: Normalize line endings (convert to \n)
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Step 2: Parse block-level elements first (tables, lists, code blocks, quotes)
    $text = parseTables($text);
    $text = parseCodeBlocks($text);
    $text = parseLists($text);
    $text = parseBlockquotes($text);
    $text = parseHeadings($text);
    
    // Step 3: Parse inline elements
    $text = parseInlineElements($text);
    
    // Step 4: Convert remaining newlines to <br>
    $text = parseNewlines($text);
    
    // Step 5: Final security cleanup (strip any remaining HTML that might be dangerous)
    $text = stripRemainingTags($text);
    
    return $text;
}

// ----------------------------------------------------------------------
// 3. Block-Level Parsers
// ----------------------------------------------------------------------

/**
 * Parse headings: #, ##, ###, ####, #####, ######
 */
function parseHeadings($text) {
    // Match headings at the start of a line (with optional leading spaces up to 3)
    $pattern = '/^( {0,3})(#{1,6})\s+(.+?)(?:\s*#+)?$/m';
    $text = preg_replace_callback($pattern, function($matches) {
        $level = strlen($matches[2]); // Number of # characters
        $content = trim($matches[3]);
        return "<h{$level}>" . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . "</h{$level}>";
    }, $text);
    return $text;
}

/**
 * Parse code blocks: ```language ... ```
 */
function parseCodeBlocks($text) {
    // Pattern for fenced code blocks
    $pattern = '/```(\w*)\n?(.*?)```/s';
    $text = preg_replace_callback($pattern, function($matches) {
        $language = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        $code = trim($matches[2], "\n");
        $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        
        $langAttr = $language ? ' class="language-' . $language . '"' : '';
        return '<pre><code' . $langAttr . '>' . $code . '</code></pre>';
    }, $text);
    return $text;
}

/**
 * Parse lists: unordered (-) and ordered (1.)
 */
function parseLists($text) {
    // Split into lines for processing
    $lines = explode("\n", $text);
    $result = [];
    $i = 0;
    $listStack = []; // Track nested lists
    
    while ($i < count($lines)) {
        $line = $lines[$i];
        $trimmed = ltrim($line);
        
        // Check for unordered list item (- Item)
        if (preg_match('/^(\s*)- (.+)$/', $line, $matches)) {
            $indent = strlen($matches[1]);
            $content = trim($matches[2]);
            $result[] = str_repeat(' ', $indent) . '<li>' . parseInlineElements($content) . '</li>';
            $i++;
            continue;
        }
        
        // Check for ordered list item (1. Item)
        if (preg_match('/^(\s*)\d+\.\s+(.+)$/', $line, $matches)) {
            $indent = strlen($matches[1]);
            $content = trim($matches[2]);
            $result[] = str_repeat(' ', $indent) . '<li>' . parseInlineElements($content) . '</li>';
            $i++;
            continue;
        }
        
        // Not a list item
        $result[] = $line;
        $i++;
    }
    
    $text = implode("\n", $result);
    
    // Wrap consecutive list items
    $text = preg_replace('/^(<li>.*?<\/li>)(\s*<li>.*?<\/li>)+$/m', '<ul>$0</ul>', $text);
    $text = preg_replace('/^(<li>.*?<\/li>)(\s*<li>.*?<\/li>)+$/m', '<ol>$0</ol>', $text);
    
    // Fallback: wrap single li items
    $text = preg_replace('/^<li>.*?<\/li>$/m', '<ul>$0</ul>', $text);
    
    return $text;
}

/**
 * Parse blockquotes: > text
 */
function parseBlockquotes($text) {
    // Simple blockquote parser - handles > at start of line
    $pattern = '/^>\s+(.+?)$/m';
    $text = preg_replace_callback($pattern, function($matches) {
        $content = trim($matches[1]);
        return '<blockquote>' . parseInlineElements($content) . '</blockquote>';
    }, $text);
    return $text;
}

/**
 * Parse tables: | Header | Header |
 */
function parseTables($text) {
    $lines = explode("\n", $text);
    $result = [];
    $i = 0;
    
    while ($i < count($lines)) {
        $line = trim($lines[$i]);
        
        // Check if this is a table row (starts and ends with |)
        if (strpos($line, '|') === 0 || strpos($line, '|') !== false) {
            $rows = [];
            
            // Collect all table rows
            while ($i < count($lines) && strpos(trim($lines[$i]), '|') !== false) {
                $rows[] = trim($lines[$i]);
                $i++;
            }
            
            if (count($rows) >= 2) {
                // Parse table
                $tableHtml = '<table>';
                $isHeader = true;
                
                foreach ($rows as $row) {
                    // Skip separator rows (|---|)
                    if (preg_match('/^\|[\s\-:|]+\|$/', $row)) {
                        $isHeader = false;
                        continue;
                    }
                    
                    // Parse cells
                    $cells = array_map('trim', explode('|', trim($row, '|')));
                    $cells = array_filter($cells, function($cell) {
                        return $cell !== '';
                    });
                    
                    if (empty($cells)) continue;
                    
                    if ($isHeader) {
                        $tableHtml .= '<tr>';
                        foreach ($cells as $cell) {
                            $tableHtml .= '<th>' . parseInlineElements($cell) . '</th>';
                        }
                        $tableHtml .= '</tr>';
                        $isHeader = false;
                    } else {
                        $tableHtml .= '<tr>';
                        foreach ($cells as $cell) {
                            $tableHtml .= '<td>' . parseInlineElements($cell) . '</td>';
                        }
                        $tableHtml .= '</tr>';
                    }
                }
                
                $tableHtml .= '</table>';
                $result[] = $tableHtml;
                continue;
            }
        }
        
        $result[] = $line;
        $i++;
    }
    
    return implode("\n", $result);
}

// ----------------------------------------------------------------------
// 4. Inline Element Parsers
// ----------------------------------------------------------------------

function parseInlineElements($text) {
    // Parse images: ![alt](src)
    $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function($matches) {
        $alt = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        $src = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
        return '<img src="' . $src . '" alt="' . $alt . '">';
    }, $text);
    
    // Parse links: [text](url)
    $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function($matches) {
        $text = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
        return '<a href="' . $url . '">' . $text . '</a>';
    }, $text);
    
    // Parse bold: **text**
    $text = preg_replace_callback('/\*\*(.+?)\*\*/', function($matches) {
        return '<strong>' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</strong>';
    }, $text);
    
    // Parse italic: *text* (but not if it's part of ** or __)
    $text = preg_replace_callback('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', function($matches) {
        return '<em>' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</em>';
    }, $text);
    
    // Parse inline code: `code`
    $text = preg_replace_callback('/`([^`]+)`/', function($matches) {
        return '<code>' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</code>';
    }, $text);
    
    // Parse checkboxes: - [x] done, - [ ] not done
    $text = preg_replace_callback('/-\s+\[([ xX])\]\s+(.+?)(?=\n|$)/', function($matches) {
        $checked = ($matches[1] == 'x' || $matches[1] == 'X') ? ' checked' : '';
        $label = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
        return '<input type="checkbox"' . $checked . '> ' . $label;
    }, $text);
    
    return $text;
}

/**
 * Parse newlines: Convert \n to <br>
 */
function parseNewlines($text) {
    // Don't convert newlines inside <pre> or <code> tags
    $text = preg_replace_callback('/<(pre|code)[^>]*>.*?<\/\1>/s', function($matches) {
        return str_replace("\n", '[[NEWLINE_PLACEHOLDER]]', $matches[0]);
    }, $text);
    
    // Convert remaining newlines to <br>
    $text = nl2br($text);
    
    // Restore newlines in pre/code blocks
    $text = str_replace('[[NEWLINE_PLACEHOLDER]]', "\n", $text);
    
    return $text;
}

// ----------------------------------------------------------------------
// 5. Security - Final Sanitization
// ----------------------------------------------------------------------

function stripRemainingTags($text) {
    // Remove any script, iframe, object, embed tags
    $text = preg_replace('/<script.*?>.*?<\/script>/is', '', $text);
    $text = preg_replace('/<iframe.*?>.*?<\/iframe>/is', '', $text);
    $text = preg_replace('/<object.*?>.*?<\/object>/is', '', $text);
    $text = preg_replace('/<embed.*?>/is', '', $text);
    $text = preg_replace('/on\w+="[^"]*"/', '', $text);
    $text = preg_replace('/javascript:/i', 'javascript:void(0)', $text);
    return $text;
}

// ----------------------------------------------------------------------
// 6. Execute Parser
// ----------------------------------------------------------------------

// Parse the input
$result = parseMarkdown($rawInput);

// If the result is empty, just output the sanitized input as plain text
if (trim($result) === '') {
    $result = htmlspecialchars($rawInput, ENT_QUOTES, 'UTF-8');
}

// Output the result
echo $result;

// ----------------------------------------------------------------------
// End of script
// ----------------------------------------------------------------------
?>