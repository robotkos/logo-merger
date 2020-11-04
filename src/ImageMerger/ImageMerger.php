<?php


namespace Markify\ImageMerger;


use Arrilot\DotEnv\DotEnv;
use Gumlet\ImageResize;
use GuzzleHttp\Client;

class ImageMerger
{
    /**
     * @var Client|null
     */
    protected $client;

    /**
     * @var string
     */
    protected $path;

    /**
     * ImageMerger constructor.
     * @param string $path
     * @param Client|null $client
     */
    public function __construct(string $path, Client $client = null)
    {
        $this->path = $path;
        $this->client = $client;
    }

    /**
     * @param string $name
     * @param string $country
     * @param string $imgLink_1
     * @param string $imgLink_2
     * @return string
     * @throws \Gumlet\ImageResizeException
     */
    public function merge2Images(string $name, string $country, string $imgLink_1, string $imgLink_2): string
    {
        $client = ($this->client !== null) ? $this->client : $this->getClient();
        if (!file_exists($imgLink_1)){
            $this->download($client, $imgLink_1, $country, $name, '.jpeg');
        }
        if (!file_exists($imgLink_2)){
            $this->download($client, $imgLink_2, $country, $name . '_2', '.jpeg');
        }
        $f = (!file_exists($imgLink_1)) ? $this->path . $country . '/' . $name . '.jpeg' : $imgLink_1;
        $s = (!file_exists($imgLink_2)) ? $this->path . $country . '/' . $name . '_2.jpeg' : $imgLink_2;
        $new = $this->path . $country . '/' . $name . '_new.jpeg';
        $newMerged = $this->path . $country . '/' . $name . '_merged.jpeg';
        $sizeS = getimagesize($s);
        $sizeF = getimagesize($f);
        //resizing smaller pic to bigger sizes
        if ($sizeF[0] + $sizeF[1] >= $sizeS[0] + $sizeS[1]) {
            $image = new ImageResize($s);
            $image->resizeToLongSide($sizeF[0], $allow_enlarge = True);
            $image->save($new);
            $sizeNew = getimagesize($new);

            $dest = imagecreatefromjpeg($new);
            $src = imagecreatefromjpeg($f);
        } else {
            $image = new ImageResize($f);
            $image->resizeToLongSide($sizeS[0], $allow_enlarge = True);
            $image->save($new);
            $sizeNew = getimagesize($new);

            $dest = imagecreatefromjpeg($new);
            $src = imagecreatefromjpeg($s);
        };

        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        if ($sizeS[0] + $sizeS[1] >= $sizeF[0] + $sizeF[1]) {
            if ($sizeS[0] >= $sizeS[1]) {
                $newEmptyImage = $this->processedWidth($dest, $src, $sizeNew, $sizeS, $new);
            } else {
                $newEmptyImage = $this->processedHeight($dest, $src, $sizeNew, $sizeS, $new);
            }
        } else {
            if ($sizeF[0] >= $sizeF[1]) {
                $newEmptyImage = $this->processedWidth($dest, $src, $sizeNew, $sizeF, $new);
            } else {
                $newEmptyImage = $this->processedHeight($dest, $src, $sizeNew, $sizeF, $new);
            }
        }

        imagejpeg($newEmptyImage, $newMerged);

        imagedestroy($dest);
        imagedestroy($src);
        unlink($new);
        return $newMerged;
    }

    protected function processedWidth($dest, $src, array $sizeNew, array $sizeS, string $new)
    {
        $newWidth = $this->sizeCorrection($sizeNew[1], $sizeS[0]);
        //creating empty image for merging 2 pics
        $newEmptyImage = imagecreatetruecolor($newWidth + 1, $sizeS[1] + $sizeNew[1] + 1);
        //adding white background
        $white = imagecolorallocate($newEmptyImage, 255, 255, 255);
        imagefilledrectangle($newEmptyImage, 0, 0, $newWidth, $sizeS[1] + $sizeNew[1], $white);
        //adding first pic to empty image
        imagecopy($newEmptyImage, $dest, 0, 0, 0, 0, $sizeNew[0], getimagesize($new)[1]); //have to play with these numbers for it to work for you, etc.
        //adding second pic to empty image
        imagecopy($newEmptyImage, $src, 0, $sizeNew[1] + 1, 0, 0, $newWidth, $sizeS[1] + $sizeNew[1]); //have to play with these numbers for it to work for you, etc.
        return $newEmptyImage;
    }

    protected function processedHeight($dest, $src, array $sizeNew, array $sizeS, string $new)
    {
        $newHeight = $this->sizeCorrection($sizeNew[1], $sizeS[1]);
        //creating empty image for merging 2 pics
        $newEmptyImage = imagecreatetruecolor($sizeS[0] + $sizeNew[0] + 1, $newHeight + 1);
        //adding white background
        $white = imagecolorallocate($newEmptyImage, 255, 255, 255);
        imagefilledrectangle($newEmptyImage, 0, 0, $sizeS[0] + $sizeNew[0], $newHeight, $white);
        //adding first pic to empty image
        imagecopy($newEmptyImage, $dest, 0, 0, 0, 0, $sizeNew[0], getimagesize($new)[1]); //have to play with these numbers for it to work for you, etc.
        //adding second pic to empty image
        imagecopy($newEmptyImage, $src, $sizeNew[0] + 1, 0, 0, 0, $sizeS[0] + $sizeNew[0], $newHeight); //have to play with these numbers for it to work for you, etc.
        return $newEmptyImage;
    }

    /**
     * @param int $h1
     * @param int $h2
     * @return int
     */
    private function sizeCorrection(int $h1, int $h2): int
    {
        return ($h1 <= $h2) ? $h2 : $h1;
    }

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        return new Client(['headers' => [
            'User-Agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:50.0) Gecko/20100101 Firefox/50.0',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Upgrade-Insecure-Requests' => '1',
        ],
            'cookies' => true,
            'proxy' => DotEnv::get('PROXY_IP')
        ]);
    }

    /**
     * @param Client $client
     * @param string $url
     * @param string $country
     * @param string $name
     * @param string $extensions
     * @return array
     */
    public function download(Client $client, string $url, string $country, string $name, string $extensions): array
    {
        $path = $this->path . $country . '/' . $name . $extensions;
        try{
            $file_path = fopen($path, 'w');
            $response = $client->get($url, ['save_to' => $file_path]);
        }catch (\Throwable $exception){
            echo $exception->getMessage();
        }

        return ['response_code' => $response->getStatusCode(), 'name' => $name];
    }
}