<?php declare(strict_types=1);

namespace Phact\Extensions\Imagine;

use Imagine\Exception\InvalidArgumentException;
use Imagine\File\Loader;
use Imagine\File\LoaderInterface;
use Imagine\Image\Metadata\MetadataReaderInterface;

class DefaultMetadataReader implements MetadataReaderInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \Imagine\Image\Metadata\MetadataReaderInterface::readFile()
     */
    public function readFile($file)
    {
        $loader = $file instanceof LoaderInterface ? $file : new Loader($file);

        return new MetadataBag(array_merge($this->getStreamMetadata($loader), $this->extractFromFile($loader)));
    }

    /**
     * {@inheritdoc}
     *
     * @see \Imagine\Image\Metadata\MetadataReaderInterface::readData()
     */
    public function readData($data, $originalResource = null)
    {
        if (null !== $originalResource) {
            return new MetadataBag(array_merge($this->getStreamMetadata($originalResource), $this->extractFromData($data)));
        }

        return new MetadataBag($this->extractFromData($data));
    }

    /**
     * {@inheritdoc}
     *
     * @see \Imagine\Image\Metadata\MetadataReaderInterface::readStream()
     */
    public function readStream($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Invalid resource provided.');
        }

        return new MetadataBag(array_merge($this->getStreamMetadata($resource), $this->extractFromStream($resource)));
    }

    /**
     * Gets the URI from a stream resource.
     *
     * @param resource|\Imagine\File\LoaderInterface $resource
     *
     * @return array
     */
    private function getStreamMetadata($resource)
    {
        $metadata = array();

        if ($resource instanceof LoaderInterface) {
            $metadata['uri'] = $resource->getPath();
            if ($resource->isLocalFile()) {
                $metadata['filepath'] = realpath($resource->getPath());
            }
        } elseif (false !== $data = @stream_get_meta_data($resource)) {
            if (isset($data['uri'])) {
                $metadata['uri'] = $data['uri'];
                if (stream_is_local($resource)) {
                    $metadata['filepath'] = realpath($data['uri']);
                }
            }
        }

        return $metadata;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Imagine\Image\Metadata\AbstractMetadataReader::extractFromFile()
     */
    protected function extractFromFile($file)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Imagine\Image\Metadata\AbstractMetadataReader::extractFromData()
     */
    protected function extractFromData($data)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Imagine\Image\Metadata\AbstractMetadataReader::extractFromStream()
     */
    protected function extractFromStream($resource)
    {
        return array();
    }
}