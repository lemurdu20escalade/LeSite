/**
 * Design Tokens Builder
 *
 * Converts W3C Design Tokens JSON to CSS Custom Properties.
 * Usage: node src/tokens/build-tokens.js
 *
 * @package Lemur
 */

import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

// ES Module __dirname equivalent
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Configuration
const CONFIG = {
  tokensPath: join(__dirname, 'tokens.json'),
  outputPath: join(__dirname, '../css/design-system/_tokens.css'),
};

/**
 * Flatten nested token object into flat key-value pairs
 *
 * @param {Object} obj - Token object to flatten
 * @param {string} prefix - Current prefix for keys
 * @returns {Object} Flattened tokens
 */
function flattenTokens(obj, prefix = '') {
  let result = {};

  for (const [key, value] of Object.entries(obj)) {
    // Skip $schema and other meta properties
    if (key.startsWith('$')) {
      continue;
    }

    const newKey = prefix ? `${prefix}-${key}` : key;

    if (value !== null && typeof value === 'object') {
      if (value.$value !== undefined) {
        // This is a token leaf node
        result[newKey] = value.$value;
      } else {
        // This is a nested object, recurse
        result = { ...result, ...flattenTokens(value, newKey) };
      }
    }
  }

  return result;
}

/**
 * Generate CSS from flattened tokens
 *
 * @param {Object} tokens - Flattened token object
 * @returns {string} CSS content
 */
function generateCSS(tokens) {
  const lines = [
    '/* ============================================',
    ' * Design Tokens - Generated automatically',
    ' * DO NOT EDIT MANUALLY',
    ' * ',
    ' * Source: src/tokens/tokens.json',
    ' * Run: npm run tokens:build',
    ' * ============================================ */',
    '',
    ':root {',
  ];

  // Group tokens by category for better readability
  const categories = {};

  for (const [key, value] of Object.entries(tokens)) {
    const category = key.split('-')[0];
    if (!categories[category]) {
      categories[category] = [];
    }
    categories[category].push({ key, value });
  }

  // Output tokens grouped by category
  for (const [category, categoryTokens] of Object.entries(categories)) {
    lines.push(`  /* ${category} */`);
    for (const { key, value } of categoryTokens) {
      lines.push(`  --${key}: ${value};`);
    }
    lines.push('');
  }

  // Remove trailing empty line inside :root
  if (lines[lines.length - 1] === '') {
    lines.pop();
  }

  lines.push('}');
  lines.push('');

  return lines.join('\n');
}

/**
 * Main build function
 */
function build() {
  console.log('[tokens] Starting build...');

  // Check if tokens file exists
  if (!existsSync(CONFIG.tokensPath)) {
    console.error(`[tokens] Error: Tokens file not found at ${CONFIG.tokensPath}`);
    process.exit(1);
  }

  // Read and parse tokens
  let tokensRaw;
  let tokens;

  try {
    tokensRaw = readFileSync(CONFIG.tokensPath, 'utf8');
  } catch (error) {
    console.error(`[tokens] Error reading file: ${error.message}`);
    process.exit(1);
  }

  try {
    tokens = JSON.parse(tokensRaw);
  } catch (error) {
    console.error(`[tokens] Error parsing JSON: ${error.message}`);
    process.exit(1);
  }

  // Flatten tokens
  const flatTokens = flattenTokens(tokens);
  const tokenCount = Object.keys(flatTokens).length;

  if (tokenCount === 0) {
    console.error('[tokens] Error: No tokens found in file');
    process.exit(1);
  }

  // Generate CSS
  const css = generateCSS(flatTokens);

  // Ensure output directory exists
  const outputDir = dirname(CONFIG.outputPath);
  if (!existsSync(outputDir)) {
    mkdirSync(outputDir, { recursive: true });
  }

  // Write CSS file
  try {
    writeFileSync(CONFIG.outputPath, css);
  } catch (error) {
    console.error(`[tokens] Error writing file: ${error.message}`);
    process.exit(1);
  }

  console.log(`[tokens] Success: ${CONFIG.outputPath}`);
  console.log(`[tokens] Generated ${tokenCount} CSS custom properties`);
}

// Run build
build();
