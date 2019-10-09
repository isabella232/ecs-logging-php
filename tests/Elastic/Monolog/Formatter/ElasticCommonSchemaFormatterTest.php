<?php declare(strict_types=1);

// Licensed to Elasticsearch B.V under one or more agreements.
// Elasticsearch B.V licenses this file to you under the Apache 2.0 License.
// See the LICENSE file in the project root for more information

namespace Elastic\Monolog\Formatter;

use Monolog\Logger;
use Elastic\Monolog\Formatter\ElasticCommonSchemaFormatter;

use Throwable;

/**
 * Test: ElasticCommonSchemaFormatter
 *
 * @version ECS v1.2.0
 *
 * @see https://www.elastic.co/guide/en/ecs/1.2/ecs-log.html
 * @see Elastic\Monolog\Formatter\ElasticCommonSchemaFormatter
 *
 * @author Philip Krauss <philip.krauss@elastic.co>
 */
class ElasticCommonSchemaFormatterTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers Elastic\Monolog\Formatter\ElasticCommonSchemaFormatter::__construct
     * @covers Elastic\Monolog\Formatter\ElasticCommonSchemaFormatter::format
     */
    public function testFormat()
    {
        $msg = [
            'level'      => Logger::INFO,
            'level_name' => 'INFO',
            'channel'    => 'ecs',
            'datetime'   => new \DateTimeImmutable("@0"),
            'message'    => md5(uniqid()),
            'context'    => [],
            'extra'      => [],
        ];

        $formatter = new ElasticCommonSchemaFormatter();
        $doc = $formatter->format($msg);

        // Must be a string terminated by a new line
        $this->assertIsString($doc);
        $this->assertStringEndsWith("\n", $doc);

        // Comply to the ECS format
        $decoded = json_decode($doc, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('@timestamp', $decoded);
        $this->assertArrayHasKey('log', $decoded);
        $this->assertArrayHasKey('level', $decoded['log']);
        $this->assertArrayHasKey('logger', $decoded['log']);
        $this->assertArrayHasKey('message', $decoded);

        // Not other keys are set for the MVP
        $this->assertEquals(['@timestamp', 'log', 'message'], array_keys($decoded));
        $this->assertEquals(['level', 'logger'], array_keys($decoded['log']));

        // Values correctly propagated
        $this->assertEquals('1970-01-01T00:00:00.000000Z', $decoded['@timestamp']);
        $this->assertEquals($msg['level_name'], $decoded['log']['level']);
        $this->assertEquals($msg['channel'], $decoded['log']['logger']);
        $this->assertEquals($msg['message'], $decoded['message']);
    }

    /**
     * @depends testFormat
     *
     * @covers Elastic\Monolog\Formatter\ElasticCommonSchemaFormatter::__construct
     */
    public function testDistributedTracing()
    {
        $msg = [
            'level'      => Logger::NOTICE,
            'level_name' => 'NOTICE',
            'channel'    => 'ecs',
            'datetime'   => new \DateTimeImmutable("@0"),
            'message'    => md5(uniqid()),
            'context'    => ['trace' => '4bf92f3577b34da6a3ce929d0e0e'.rand(1000, 9999), 'transaction' => '00f067aa0ba90'.rand(100, 999)],
            'extra'      => [],
        ];

        $formatter = new ElasticCommonSchemaFormatter();
        $doc = $formatter->format($msg);

        $decoded = json_decode($doc, true);
        $this->assertArrayHasKey('trace', $decoded);
        $this->assertArrayHasKey('transaction', $decoded);
        $this->assertArrayHasKey('id', $decoded['trace']);
        $this->assertArrayHasKey('id', $decoded['transaction']);

        $this->assertEquals($msg['context']['trace'], $decoded['trace']['id']);
        $this->assertEquals($msg['context']['transaction'], $decoded['transaction']['id']);
    }

    /**
     * @depends testFormat
     *
     * @covers Elastic\Monolog\Formatter\ElasticCommonSchemaFormatter::__construct
     */
    public function testTags()
    {
        $msg = [
            'level'      => Logger::ERROR,
            'level_name' => 'ERROR',
            'channel'    => 'ecs',
            'datetime'   => new \DateTimeImmutable("@0"),
            'message'    => md5(uniqid()),
            'context'    => [],
            'extra'      => [],
        ];

        $tags = [
            'one',
            'two',
        ];

        $formatter = new ElasticCommonSchemaFormatter($tags);
        $doc = $formatter->format($msg);

        $decoded = json_decode($doc, true);
        $this->assertArrayHasKey('tags', $decoded);
        $this->assertEquals($tags, $decoded['tags']);
    }

    /**
     * @depends testFormat
     *
     * @covers Elastic\Monolog\Formatter\ElasticCommonSchemaFormatter::__construct
     * @covers Elastic\Monolog\Formatter\ElasticCommonSchemaFormatter::normalizeException
     */
    public function testNormalizeException()
    {
        // TODO
        $this->assertTrue(true);
    }
}