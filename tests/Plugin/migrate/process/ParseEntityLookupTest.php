<?php

namespace Drupal\idc_migration\Plugin\migrate\process;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

class ParseEntityLookupTest extends MigrateProcessTestCase {

    /**
     * 'entity_lookup' plugin definition
     */
    private static $entity_lookup_plugindef = [
        'handle_multiples' => true,
        'id' => 'entity_lookup',
        'class' => 'Drupal\migrate_plus\Plugin\migrate\process\EntityLookup',
        'provider' => 'migrate_plus'
    ];

    /**
     * 'parse_entity_lookup' plugin definition
     */
    private static $parse_entity_lookup_plugindef = [
        'handle_multiples' => true,
        'id' => 'parse_entity_lookup',
        'class' => 'Drupal\idc_migration\Plugin\migrate\process',
        'provider' => 'idc_migration'
    ];

    /**
     * 'parse_entity_lookup' plugin configuration
     */
    private static $parse_entity_lookup_config = [
        'delimiter' => ':',
        'defaults' => [
            'entity_type' => 'taxonomy_term',
            'bundle_key' => 'vid',
            'bundle' => 'subject',
            'value_key' => 'name',
        ],
    ];

    /**
     * Mock instance of MigratePluginManager
     */
    private $mockPluginMgr;

    /**
     * Mock instance of EntityLookup plugin
     */
    private $mockEntityLookupPlugin;

    /**
     * Mock instance of MigrationExecutableInterface
     */
    private $mockMigrationExe;

    /**
     * Mock instance of MigrationInterface
     */
    private $mockMigration;

    /**
     * Mock \Drupal\migrate\Row
     */
    private $mockRow;

    /**
     * Instance of ParseEntityLookup plugin under test
     */
    private $underTest;

    protected function setUp() {
        parent::setUp();

        $this->mockPluginMgr = $this->getMockBuilder(MigratePluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockEntityLookupPlugin = $this->getMockBuilder(EntityLookup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockMigrationExe = $this->getMockBuilder(MigrateExecutableInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockMigration = $this->getMockBuilder(MigrationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRow = $this->getMockBuilder(Row::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = new ContainerBuilder();
        $container->set(ParseEntityLookup::$migrate_plugin_manager, $this->mockPluginMgr);
        \Drupal::setContainer($container);

        $this->underTest = ParseEntityLookup::create(
            $container,
            self::$parse_entity_lookup_config,
            self::$parse_entity_lookup_plugindef['id'],
            self::$parse_entity_lookup_plugindef,
            $this->mockMigration);
    }

    /**
     * Given expected input, a valid entity_lookup configuration should be returned
     */
    public function testToEntityLookupConfigOk() {
        $split_values = [
            0 => 'taxonomy_term',
            1 => 'subject',
            2 => 'name',
            3 => 'History',
        ];

        $config = $this->underTest->toEntityLookupConfig($split_values);

        self::assertNotEmpty($config);

        // The 4th element (e.g. 'History') is not part of the configuration
        self::assertEquals(3, sizeof($config));
        self::assertEquals('taxonomy_term', $config['entity_type']);
        self::assertEquals('subject', $config['bundle']);
        self::assertEquals('name', $config['value_key']);
    }

    /**
     * Null values should be elided from the returned entity_lookup configuration
     */
    public function testToEntityLookupNullValue() {
        $split_values = [
            0 => 'taxonomy_term',
            1 => 'subject',
            2 => NULL,
            3 => 'History',
        ];

        $config = $this->underTest->toEntityLookupConfig($split_values);

        self::assertNotEmpty($config);

        self::assertEquals(2, sizeof($config));
        self::assertEquals('taxonomy_term', $config['entity_type']);
        self::assertEquals('subject', $config['bundle']);
    }

    /**
     * Zero-length strings should be elided from the returned entity_lookup configuration
     */
    public function testToEntityLookupZeroLengthStringValue() {
        $split_values = [
            0 => 'taxonomy_term',
            1 => '',
            2 => 'name',
            3 => 'History',
        ];

        $config = $this->underTest->toEntityLookupConfig($split_values);

        self::assertNotEmpty($config);

        self::assertEquals(2, sizeof($config));
        self::assertEquals('taxonomy_term', $config['entity_type']);
        self::assertEquals('name', $config['value_key']);
    }

    /**
     * Empty strings should be elided from the returned entity_lookup configuration
     */
    public function testToEntityLookupEmptyStringValue() {
        $split_values = [
            0 => 'taxonomy_term',
            1 => '  ',
            2 => 'name',
            3 => 'History',
        ];

        $config = $this->underTest->toEntityLookupConfig($split_values);

        self::assertNotEmpty($config);

        self::assertEquals(2, sizeof($config));
        self::assertEquals('taxonomy_term', $config['entity_type']);
        self::assertEquals('name', $config['value_key']);
    }

    /**
     * Simple decoding test using a lower-case encoded delimiter
     */
    public function testDecodeLowercaseOk() {
        self::assertEquals("foo:bar", $this->underTest->decode(":", "foo%3abar"));
    }

    /**
     * Simple decoding test using an upper-case encoded delimiter
     */
    public function testDecodeUppercaseOk() {
        self::assertEquals("foo:bar", $this->underTest->decode(":", "foo%3Abar"));
    }

    /**
     * Simple decoding test where there is no delimiter present to decode
     */
    public function testDecodeNoDelimiterOk() {
        self::assertEquals("foobar", $this->underTest->decode(":", "foobar"));
    }

    /**
     * Simple decoding test insuring RFC 3986 reserved characters can be decoded
     */
    public function testDecodeRfc3986ReservedCharacters() {
        self::assertEquals("foo-bar", $this->underTest->decode("-", "foo%2dbar"));
        self::assertEquals("foo_bar", $this->underTest->decode("_", "foo%5fbar"));
        self::assertEquals("foo.bar", $this->underTest->decode(".", "foo%2ebar"));
        self::assertEquals("foo~bar", $this->underTest->decode("~", "foo%7ebar"));
    }

    /**
     * Insure a '%' can be used as a delimiter
     */
    public function testDecodePercentDelimiter() {
        self::assertEquals("foo%bar", $this->underTest->decode("%", "foo%25bar"));
    }

    /**
     * Other potential delimiters
     */
    public function testDecodeOtherPotentialDelimiters() {
        self::assertEquals("foo;bar", $this->underTest->decode(";", "foo%3bbar"));
        self::assertEquals("foo bar", $this->underTest->decode(" ", "foo%20bar"));
        self::assertEquals("foo+bar", $this->underTest->decode("+", "foo%2bbar"));
        self::assertEquals("foo@bar", $this->underTest->decode("@", "foo%40bar"));
        self::assertEquals("foo#bar", $this->underTest->decode("#", "foo%23bar"));
        self::assertEquals("foo|bar", $this->underTest->decode("|", "foo%7cbar"));
    }

    /**
     * Insure multi-character delimiter strings are supported
     */
    public function testDecodeMulticharacterDelimiter() {
        self::assertEquals("foo  bar", $this->underTest->decode("  ", "foo%20%20bar"));
        self::assertEquals("foo ;bar", $this->underTest->decode(" ;", "foo%20%3bbar"));
    }

    /**
     * Insure the defaults supplied from the plugin configuration will be present in the merged array if elided from
     * source config.
     */
    public function testApplyDefaultsSuppliesElidedValues() {
        $result = $this->underTest->applyDefaults(
            [
                'source-value' => 'foo'
            ],
            [
                'default-value' => 'bar'
            ]);

        self::assertEquals(2, sizeof($result));
        self::assertTrue(array_key_exists('source-value', $result));
        self::assertTrue(array_key_exists('default-value', $result));
        self::assertEquals('foo', $result['source-value']);
        self::assertEquals('bar', $result['default-value']);
    }

    /**
     * Insure the values from the source config override values provided by defaults when the same key is shared.
     */
    public function testApplyDefaultsOverriddenBySourceValues() {
        $result = $this->underTest->applyDefaults(
            [
                'a-value' => 'foo'
            ],
            [
                'a-value' => 'bar'
            ]);

        self::assertEquals(1, sizeof($result));
        self::assertTrue(array_key_exists('a-value', $result));
        self::assertEquals('foo', $result['a-value']);
    }

    /**
     * Big picture: the transform() method of the parse_entity_lookup plugin (the instance under test) creates an
     * instance of the entity_lookup plugin and delegates to its transform method.  The _configuration_ supplied to the
     * entity_lookup plugin is parsed from the _source_ values obtains from the spreadsheet.<br><br>
     *
     * Essentially, values from the spreadsheet parameterize the configuration for the entity_lookup plugin.<br><br>
     *
     * In this test, we simply verify that each mock receives the expected arguments when it is invoked.
     */
    public function testTransformOk() {
        // The source value from the spreadsheet in the form:
        //   <entity type>:<bundle>:<value_key>:<value>
        // Elided values from the source_value are provided by the 'defaults' configuration key of the
        // parse_entity_lookup plugin.
        $source_value = ":subject:name:History";

        // Entity Lookup plugin instance that will be returned by MigratePluginManager::createInstance(...)
        $entity_lookup_plugin = $this->mockEntityLookupPlugin;

        // Mocks MigratePluginManager::createInstance.
        // Used to create the instance of the entity_lookup plugin.
        $createInstanceInvoked = false;
        $this->mockPluginMgr->expects($this->any())
            ->method('createInstance')
            ->willReturnCallback(function ($id, $config, $migration) use ($entity_lookup_plugin, &$createInstanceInvoked) {
                self::assertEquals('entity_lookup', $id);
                self::assertNotNull($migration);

                // This is the configuration for the entity_lookup plugin, created by combining values parsed from the
                // $source_value and parse_entity_lookup configuration defaults.
                self::assertNotempty($config);
                self::assertEquals([
                    'entity_type' => 'taxonomy_term',
                    'bundle_key' => 'vid',
                    'bundle' => 'subject',
                    'value_key' => 'name'
                ], $config);

                $createInstanceInvoked = true;
                return $entity_lookup_plugin;
            });

        // Mock MigrateProcessInterface::transform of the entity_lookup plugin.
        // Simply assert that we receive the expected values from the parse_entity_lookup plugin.
        // Returns a random value.
        $transformInvoked = false;
        $this->mockEntityLookupPlugin->expects($this->once())
            ->method('transform')
            ->willReturnCallback(function ($value, $migrate_executable, $row, $destination_property) use (&$transformInvoked) {
                self::assertEquals("History", $value);
                self::assertNotNull($migrate_executable);
                $transformInvoked = true;
                return "1234";
            });

        $result = $this->underTest->transform($source_value, $this->mockMigrationExe, new Row(), "foo");

        self::assertEquals("1234", $result);

        // verify mocks (TODO: consider using Prophecy for mock verification)
        self::assertTrue($createInstanceInvoked);
        self::assertTrue($transformInvoked);
    }

    public function testTransformInvalidSourceValueTooSmall() {
        // The source value from the spreadsheet in the form:
        //   <entity type>:<bundle>:<value_key>:<value>
        // Purposefully made too small here.
        $source_value = "subject:name:History";

        try {
            $this->underTest->transform($source_value, $this->mockMigrationExe, new Row(), "foo");
            self::fail("Expected transform() to throw a MigrateException");
        } catch (MigrateException $e) {
            self::assertTrue(str_contains($e->getMessage(), $source_value));
            self::assertTrue(str_contains($e->getMessage(), ParseEntityLookup::expected_source_arraysize));
            self::assertTrue(str_contains($e->getMessage(), sizeof(explode(':', $source_value))));
        }
    }

    public function testTransformInvalidSourceValueTooBig() {
        // The source value from the spreadsheet in the form:
        //   <entity type>:<bundle>:<value_key>:<value>
        // Purposefully made too big here.
        $source_value = "::subject:name:History";

        try {
            $this->underTest->transform($source_value, $this->mockMigrationExe, new Row(), "foo");
            self::fail("Expected transform() to throw a MigrateException");
        } catch (MigrateException $e) {
            self::assertTrue(str_contains($e->getMessage(), $source_value));
            self::assertTrue(str_contains($e->getMessage(), ParseEntityLookup::expected_source_arraysize));
            self::assertTrue(str_contains($e->getMessage(), sizeof(explode(':', $source_value))));
        }
    }

    public function testTransformInvalidSourceValueEmptyLastElement() {
        // The source value from the spreadsheet in the form:
        //   <entity type>:<bundle>:<value_key>:<value>
        // Purposefully empty last element.
        $source_value = "taxonomy_term:subject:name:";

        try {
            $this->underTest->transform($source_value, $this->mockMigrationExe, new Row(), "foo");
            self::fail("Expected transform() to throw a MigrateException");
        } catch (MigrateException $e) {
            self::assertTrue(str_contains($e->getMessage(), $source_value));
            self::assertTrue(str_contains($e->getMessage(), "must not be empty"));
        }
    }

    /**
     * Empty source values should result in NULL being returned.
     */
    public function testEmptyValue() {
        $source_value = " ";
        $result = $this->underTest->transform($source_value, $this->mockMigrationExe, new Row(), "foo");
        self::assertNull($result);
    }

    /**
     * Zero-length source values should result in NULL being returned.
     */
    public function testZeroLengthValue() {
        $source_value = "";
        $result = $this->underTest->transform($source_value, $this->mockMigrationExe, new Row(), "foo");
        self::assertNull($result);
    }

    /**
     * NULL source values should result in NULL being returned.
     */
    public function testNullValue() {
        $source_value = NULL;
        $result = $this->underTest->transform($source_value, $this->mockMigrationExe, new Row(), "foo");
        self::assertNull($result);
    }
}