<?php

/**
 * This file is part of the nexphant Framework.
 *
 * (c) nexphant <https://github.com/nexphant>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Nexphant\Foundation\Metadata;

use Nexphant\Foundation\MetadataRegistry;

/**
 * IdeMetadata — exports metadata in a format useful for IDE helpers and static analysis.
 */
class IdeMetadata
{
    public function __construct(
        private readonly MetadataRegistry $registry,
    ) {}

    /**
     * Export all registered metadata groups to a single array.
     */
    public function export(): array
    {
        $groups = ['model', 'validation', 'database', 'form'];
        $result = [];
        foreach ($groups as $group) {
            $result[$group] = $this->registry->group($group);
        }
        return $result;
    }

    /**
     * Write IDE metadata PHP file for static analysis tooling.
     */
    public function writePhp(string $path): void
    {
        $data    = $this->export();
        $content = '<?php' . PHP_EOL . '// Auto-generated IDE metadata' . PHP_EOL
            . 'return ' . var_export($data, true) . ';' . PHP_EOL;
        file_put_contents($path, $content, LOCK_EX);
    }
}
