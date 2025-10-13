<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Services\NewsAggregator\AggregatorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Js;

class SourceController extends Controller
{
    public function __construct(protected AggregatorService $aggregatorService){}

    /**
     * Summary of index
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $sources = Cache::remember("source_list", 3600, function () {
            return Source::active()->get()->map(function ($source) {
                return [
                    'name' => $source->name,
                    'identifier' => $source->identifier,
                    'api_name' => $source->api_name,
                    'url' => $source->url,
                    'description' => $source->description,
                    'language' => $source->language,
                    'country' => $source->country,
                    'categories' => $source->categories,
                    'is_active' => $source->is_active,
                ];
            });
        });

        return response()->json([
            'data' => $sources,
            'meta' => [
                'total' => $sources->count(),
            ],
        ]);
    }

    /**
     * Summary of categories
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories() 
    {
        $categories = Cache::remember("all_categories", 3600, function () {
            $sources = $this->aggregatorService->getAvailableSources();
            $allCategories = [];

            foreach ($sources as $source) {
                if (is_array($source['categories'])) {
                    $allCategories = array_merge($allCategories, $source['categories']);
                }
            }
            return array_unique($allCategories);
        });

        sort($categories);

        return response()->json([
            'data' => $categories,
            'meta' => [
                'total' => count($categories),
            ],
        ]);
    }

    /**
     * Summary of status
     * 
     * @param string $source
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(string $source)
    {
        $sources = $this->aggregatorService->getAvailableSources();

        $sourceData = $sources->firstWhere('name', $source);

        if (!$sourceData) {
            return response()->json([
                'error' => 'Source not found',
            ], 404);
        }

        
        return response()->json([
            'data' => [
                'name' => $sourceData['name'],
                'available' => $sourceData['available'],
                'categories' => $sourceData['categories'] ?? [],
                'last_fetched' => Source::where('name', $source)->value('last_fetched_at'),
            ]
        ]);
    }

    /**
     * Summary of toggle
     * 
     * @param string $source
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(string $source)
    {
        $updated = Source::where('identifier', $source)
            ->update([
                'is_active' => DB::raw('NOT is_active'),
                'updated_at' => now()
            ]);

        if ($updated) {
            Cache::forget("source_list");
            return response()->json([
                'message' => 'Source status updated successfully.',
            ]);
        }

        return response()->json([
            'error' => 'Failed to update source status.',
        ], 500);
    }
}
