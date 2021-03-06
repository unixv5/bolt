<?php
namespace Bolt\Asset\File;

use Bolt\Asset\AssetSortTrait;
use Bolt\Asset\Injector;
use Bolt\Asset\QueueInterface;
use Bolt\Asset\Target;
use Bolt\Controller\Zone;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * File asset queue processor.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Queue implements QueueInterface
{
    use AssetSortTrait;

    /** @var \Bolt\Asset\Injector */
    protected $injector;
    /** @var Packages */
    protected $packages;

    /** @var FileAssetInterface[] */
    private $stylesheet = [];
    /** @var FileAssetInterface[] */
    private $javascript = [];

    /**
     * Constructor.
     *
     * @param Injector $injector
     * @param Packages $packages
     */
    public function __construct(Injector $injector, Packages $packages)
    {
        $this->injector = $injector;
        $this->packages = $packages;
    }

    /**
     * Add a file asset to the queue.
     *
     * @param FileAssetInterface $asset
     *
     * @throws \InvalidArgumentException
     */
    public function add(FileAssetInterface $asset)
    {
        $url = $this->packages->getUrl($asset->getPath(), $asset->getPackageName());
        $asset->setUrl($url);

        if ($asset->getType() === 'javascript') {
            $this->javascript[$url] = $asset;
        } elseif ($asset->getType() === 'stylesheet') {
            $this->stylesheet[$url] = $asset;
        } else {
            throw new \InvalidArgumentException(sprintf('Requested asset type %s is not valid.', $asset->getType()));
        }
    }

    /**
     * {@inheritdoc}
     *
     * Uses sorting by priority.
     */
    public function process(Request $request, Response $response)
    {
        /** @var FileAssetInterface $asset */
        foreach ($this->sort($this->javascript) as $key => $asset) {
            $this->processAsset($asset, $request, $response);
            unset($this->javascript[$key]);
        }

        /** @var FileAssetInterface $asset */
        foreach ($this->sort($this->stylesheet) as $key => $asset) {
            $this->processAsset($asset, $request, $response);
            unset($this->stylesheet[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue()
    {
        return [
            'javascript' => $this->javascript,
            'stylesheet' => $this->stylesheet,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->stylesheet = [];
        $this->javascript = [];
    }

    /**
     * Process a single asset.
     *
     * @param FileAssetInterface $asset
     * @param Request            $request
     * @param Response           $response
     */
    protected function processAsset(FileAssetInterface $asset, Request $request, Response $response)
    {
        if ($asset->getZone() !== Zone::get($request)) {
            return;
        } elseif ($asset->isLate()) {
            $this->injector->inject($asset, Target::END_OF_BODY, $response);
        } elseif ($asset->getType() === 'stylesheet') {
            $this->injector->inject($asset, Target::BEFORE_CSS, $response);
        } elseif ($asset->getType() === 'javascript') {
            $this->injector->inject($asset, Target::AFTER_JS, $response);
        }
    }
}
