<?php

namespace App\Helpers;

class ArrayHelpers {
    public static function chunk_file(string $filepath, int $dataset_id, callable $generator, int $chunk_size) {
        $file = fopen($filepath, 'r');
        $data = [];

        for ($i = 0; ($row = fgetcsv($file, null, ',')) !== false; $i++) {
            # Read each row in the csv file
            $data[] = $generator($dataset_id, $row);

            # Yield data when chunk size has been reached
            if ($i % $chunk_size === 0) {
                yield $data;
                $data = [];
            }
        }

        # Any remaining data will be yielded
        if (!empty($data)) {
            yield $data;
        }

        fclose($file);
    }
}