<?php

namespace App\Console\Commands;

use App\Models\HelpEmbedding;
use App\Services\Help\HelpManualChunker;
use App\Services\Help\HelpOpenRouterClient;
use Illuminate\Console\Command;
use Throwable;

class HelpReindexCommand extends Command
{
    protected $signature = 'help:reindex';

    protected $description = 'Rebuild help_embeddings from docs/manuals (chunk, embed via OpenRouter, store)';

    public function handle(HelpManualChunker $chunker, HelpOpenRouterClient $client): int
    {
        $chunks = $chunker->chunkAll();

        if ($chunks === []) {
            $this->warn('No chunks found. Add markdown files to docs/manuals/ or help-navigation.json.');
        }

        HelpEmbedding::query()->delete();

        $batchSize = max(1, (int) config('help.reindex_batch_size'));
        $total = count($chunks);
        $done = 0;

        foreach (array_chunk($chunks, $batchSize) as $batch) {
            try {
                $inputs = array_column($batch, 'content');
                $vectors = $client->embedMany($inputs);
            } catch (Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            if (count($vectors) !== count($batch)) {
                $this->error('Embedding API returned an unexpected number of vectors.');

                return self::FAILURE;
            }

            foreach ($batch as $i => $row) {
                HelpEmbedding::query()->create([
                    'chunk_key' => $row['chunk_key'],
                    'source_path' => $row['source_path'],
                    'heading' => $row['heading'],
                    'locale' => $row['locale'],
                    'content' => $row['content'],
                    'embedding' => $vectors[$i],
                ]);
                $done++;
            }

            $this->line("Indexed {$done}/{$total} chunks…");
        }

        $this->info("Done. {$done} chunk(s) stored in help_embeddings.");

        return self::SUCCESS;
    }
}
