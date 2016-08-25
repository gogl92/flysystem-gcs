<?php

namespace CedricZiel\FlysystemGcs\Tests;

use CedricZiel\FlysystemGcs\GoogleCloudStorageAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class GoogleCloudStorageAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $project;

    /**
     * @var string
     */
    protected $bucket;

    public function setUp()
    {
        $this->project = getenv('GCLOUD_PROJECT');
        $this->bucket = getenv('GCLOUD_BUCKET');
    }

    /**
     * @test
     */
    public function testAnAdapterCanBeCreatedWithMinimalConfiguration()
    {
        $minimalConfig = [
            'bucket'    => 'test',
            'projectId' => 'test',
        ];

        $adapter = new GoogleCloudStorageAdapter(null, $minimalConfig);
    }

    public function testCanListContentsOfADirectory()
    {
        $testDirectory = '/'.uniqid('test_', true).'/';
        $minimalConfig = [
            'bucket'    => $this->bucket,
            'projectId' => $this->project,
        ];

        $config = new Config([]);
        $adapter = new GoogleCloudStorageAdapter(null, $minimalConfig);

        $contents = $adapter->listContents('/');

        $this->assertNotFalse($adapter->createDir($testDirectory, $config));
        $this->assertTrue($adapter->has($testDirectory));

        $testContents = $adapter->listContents($testDirectory);

        $this->assertTrue($adapter->deleteDir($testDirectory));
        $this->assertFalse($adapter->has($testDirectory));
        $this->assertFalse($adapter->has(ltrim($testDirectory, '/')));
        $this->assertFalse($adapter->has(rtrim(ltrim($testDirectory, '/'), '/')));
    }

    /**
     * @test
     */
    public function testFilesCanBeUploaded()
    {
        $testId = uniqid('', true);
        $destinationPath = "/test_content/{$testId}_public_test.jpg";

        $minimalConfig = [
            'bucket'    => $this->bucket,
            'projectId' => $this->project,
        ];

        $adapter = new GoogleCloudStorageAdapter(null, $minimalConfig);

        $config = new Config([]);
        $adapter->write(
            $destinationPath,
            file_get_contents(__DIR__.'/../res/Auswahl_126.png'),
            $config
        );

        $this->assertTrue($adapter->has($destinationPath));

        $adapter->setVisibility($destinationPath, AdapterInterface::VISIBILITY_PUBLIC);

        // check visibility
        $this->assertTrue($adapter->delete($destinationPath));
        $this->assertFalse($adapter->has($destinationPath));

        $testId = uniqid('', true);
        $destinationPath = "/test_content/{$testId}_private_test.jpg";

        $config = new Config([]);
        $adapter->write(
            $destinationPath,
            file_get_contents(__DIR__.'/../res/Auswahl_126.png'),
            $config
        );

        $this->assertTrue($adapter->has($destinationPath));

        $adapter->setVisibility($destinationPath, AdapterInterface::VISIBILITY_PUBLIC);
        $adapter->setVisibility($destinationPath, AdapterInterface::VISIBILITY_PRIVATE);

        $this->assertTrue($adapter->delete($destinationPath));
        $this->assertFalse($adapter->has($destinationPath));
    }

    /**
     * @test
     */
    public function canCreateDirectories()
    {
        $testId = uniqid('', true);
        $destinationPath = "/test_content{$testId}";

        $minimalConfig = [
            'bucket'    => $this->bucket,
            'projectId' => $this->project,
        ];

        $adapter = new GoogleCloudStorageAdapter(null, $minimalConfig);

        $config = new Config([]);
        $this->assertNotFalse($adapter->createDir($destinationPath, $config));
        $this->assertTrue($adapter->deleteDir($destinationPath));
    }

    /**
     * @test
     */
    public function testAFileCanBeRead()
    {
        $testId = uniqid('', true);
        $destinationPath = "/test_{$testId}_text.txt";
        $content = 'TestContent';

        $minimalConfig = [
            'bucket'    => $this->bucket,
            'projectId' => $this->project,
        ];

        $adapter = new GoogleCloudStorageAdapter(null, $minimalConfig);

        $config = new Config([]);
        $adapter->write($destinationPath, $content, $config);

        $this->assertTrue($adapter->has($destinationPath), 'Once writte, the adapter can see the object in GCS');
        $this->assertEquals(
            $content,
            $adapter->read($destinationPath),
            'The content of the GCS object matches exactly the input'
        );
        $this->assertEquals(
            'file',
            $adapter->getMetadata($destinationPath)['type'],
            'The object type is interpreted correctly'
        );
        $this->assertEquals('text/plain', $adapter->getMimetype($destinationPath)['mimetype'], 'The mime type is available');
        $this->assertEquals(
            11,
            $adapter->getSize($destinationPath)['size'],
            'The size from the metadata matches the input'
        );
        $this->assertInternalType(
            'int',
            $adapter->getTimestamp($destinationPath)['timestamp'],
            'The object has a timestamp'
        );
        $this->assertGreaterThan(
            0,
            $adapter->getTimestamp($destinationPath)['timestamp'],
            'The timestamp from GCS is added to the metadata correctly'
        );
        $this->assertTrue($adapter->delete($destinationPath));
        $this->assertFalse($adapter->has($destinationPath));
    }

    /**
     * @test
     */
    public function testDeletingNonExistentObjectsWillNotFail()
    {
        $minimalConfig = [
            'bucket'    => $this->bucket,
            'projectId' => $this->project,
        ];

        $adapter = new GoogleCloudStorageAdapter(null, $minimalConfig);

        $this->assertTrue($adapter->delete('no_file_in_storage.txt'));
    }
}
