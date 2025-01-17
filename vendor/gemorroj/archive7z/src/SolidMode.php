<?php

namespace Archive7z;

/**
 * @see https://documentation.help/7-Zip/method.htm#Solid
 */
class SolidMode /* implements \Stringable */
{
    public const ON = 'on';
    public const OFF = 'off';
    /**
     * Use a separate solid block for each new file extension.
     */
    public const E = 'e';

    /**
     * @var string|null
     */
    private $mode = self::ON;

    /**
     * @var int|null
     */
    private $filesLimit;
    /**
     * @var int|null
     */
    private $totalSizeLimit;

    /**
     * @throws Exception
     *
     * @return $this
     */
    public function setMode(string $mode): self
    {
        if (!\in_array($mode, [self::ON, self::OFF, self::E], true)) {
            throw new Exception('Invalid solid mode');
        }

        $this->mode = $mode;
        $this->filesLimit = null;
        $this->totalSizeLimit = null;

        return $this;
    }

    /**
     * @return $this
     */
    public function setFilesLimit(int $limit): self
    {
        $this->filesLimit = $limit;
        $this->mode = null;

        return $this;
    }

    /**
     * Limit in bytes.
     *
     * @return $this
     */
    public function setTotalSizeLimit(int $limit): self
    {
        $this->totalSizeLimit = $limit;
        $this->mode = null;

        return $this;
    }

    public function __toString(): string
    {
        if (null !== $this->totalSizeLimit || null !== $this->filesLimit) {
            $mode = '';
            if (null !== $this->filesLimit) {
                $mode .= $this->filesLimit.'f';
            }
            if (null !== $this->totalSizeLimit) {
                $mode .= $this->totalSizeLimit.'b';
            }

            return $mode;
        }

        return $this->mode;
    }
}
