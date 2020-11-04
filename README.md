## Image Merger

Merging 2 images in jpg format into one by downloading them first with automated resizing.


How to Use this Library?

in your composer json file add the following

```

{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:robotkos/logo-merger.git"
        }
    ]
}
```

and run  `composer require "robotkos/logo-merger"`


      ```php
     $merger = new ImageMerger($path, $client);
     $result_link = $merger->merge2Images($file_name,  $sub_folder, $link_1, $link_2);
 ```
     Where:
     $path - path to images folder (for example __DIR__ . '/../../../logo/');
     $client - Guzzle client (not required);
     $file_name - filename;
     $sub_folder = additional folder tag (for example 'ie_logo')
     $link_1 - link on img;
     $link_2 - link on img;
     
     ToDo: add other image formats
     