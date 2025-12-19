<?php

namespace App\Http\Controllers;

use App\Services\HealthStatsService;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PDFExportController extends Controller
{
    protected $healthService;
    private $chartColors = [
        'primary' => '#3A506B',
        'accent' => '#00A8A8',
        'alt' => '#9FB3C8',
        'background' => '#F4F7F8'
    ];

    public function __construct(HealthStatsService $healthService)
    {
        $this->healthService = $healthService;
    }

    /**
     * Export health insights to PDF
     */
    public function exportHealthPDF(Request $request)
    {
        // Time limit
        set_time_limit(120);
        ini_set('memory_limit', '256M');
        
        try {
            $userId = Auth::id();
            $range = $request->input('range', 'today');
            $date = now()->format('Y-m-d');

            $allData = $this->getAllHealthData($userId, $range);
            
            if (is_null($allData)) {
                $data = $this->getEmptyData($range);
            } else {
                $data = $allData;
                $data['charts'] = $this->generateCharts($data['stats'], $data['chartData'], $range);
            }
            
            // Generate PDF
            $pdf = Pdf::loadView('pdf.health-report', $data);
            
            // PDF settings
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true, 
                'defaultFont' => 'sans-serif',
                'compress' => true,
            ]);
            
            // Download
            return $pdf->download("health-report-{$range}-{$date}.pdf");
            
        } catch (\Exception $e) {
            Log::error('PDF Generation Error: ' . $e->getMessage());
            return response()->view('pdf.error', [
                'message' => 'Failed to generate PDF. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Preview
     */
    public function previewHealthPDF(Request $request)
    {
        try {
            $userId = Auth::id();
            $range = $request->input('range', 'today');
            $allData = $this->getAllHealthData($userId, $range);
            
            if (is_null($allData)) {
                $data = $this->getEmptyData($range);
            } else {
                $data = $allData;
                $data['charts'] = $this->generateCharts($data['stats'], $data['chartData'], $range);
            }
            
            return view('pdf.health-report', $data);
            
        } catch (\Exception $e) {
            Log::error('PDF Preview Error: ' . $e->getMessage());
            return view('pdf.error', [
                'message' => 'Failed to load preview. Please try again.'
            ]);
        }
    }
    
    /**
     * Generate base64 iamges using QuickChart.io
     */
    private function generateCharts($stats, $chartData, $range)
    {
        $charts = [];
        
        try {
            // 1. Sitting vs Standing
            $charts['timeDistribution'] = $this->generateDoughnutChart(
                ['Sitting', 'Standing'],
                [$stats['sitting_pct'] ?? 65, $stats['standing_pct'] ?? 35],
                [$this->chartColors['primary'], $this->chartColors['accent']],
                'Time Distribution (%)'
            );
            
            // 2. Absolute Hours
            $charts['timeAbsolute'] = $this->generateBarChart(
                ['Sitting', 'Standing'],
                [$stats['sitting_hours'] ?? 0, $stats['standing_hours'] ?? 0],
                [$this->chartColors['primary'], $this->chartColors['accent']],
                'Absolute Time (hours)'
            );
            
            // 3. Posture Score
            if (!empty($chartData['labels']) && !empty($chartData['posture_scores'])) {
                $charts['postureScore'] = $this->generateLineChart(
                    $chartData['labels'],
                    $chartData['posture_scores'],
                    $this->chartColors['primary'],
                    'Posture Score Over Time',
                    'Score'
                );
            }
            
            // 4. Average Height
            if (!empty($chartData['labels']) && !empty($chartData['avg_sit_heights']) && !empty($chartData['avg_stand_heights'])) {
                $charts['heightAverage'] = $this->generateMultiLineChart(
                    $chartData['labels'],
                    [
                        ['label' => 'Sitting Height', 'data' => $chartData['avg_sit_heights'], 'color' => $this->chartColors['alt']],
                        ['label' => 'Standing Height', 'data' => $chartData['avg_stand_heights'], 'color' => $this->chartColors['accent']]
                    ],
                    'Average Desk Height (cm)',
                    'Height (cm)'
                );
            }
            
        } catch (\Exception $e) {
            Log::warning('Chart generation failed: ' . $e->getMessage());
        }
        
        return $charts;
    }
    
    private function generateDoughnutChart($labels, $data, $colors, $title)
    {
        $chartConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 1,
                    'borderColor' => '#fff'
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                        'labels' => [
                            'font' => [
                                'size' => 10,
                                'family' => 'Arial'
                            ]
                        ]
                    ],
                    'title' => [
                        'display' => true,
                        'text' => $title,
                        'font' => [
                            'size' => 12,
                            'family' => 'Arial'
                        ]
                    ]
                ],
                'responsive' => true,
                'maintainAspectRatio' => false
            ]
        ];
        
        return $this->getChartImage($chartConfig, 400, 300);
    }
    
    private function generateBarChart($labels, $data, $colors, $title)
    {
        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderRadius' => 4,
                    'borderWidth' => 0
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'display' => false
                    ],
                    'title' => [
                        'display' => true,
                        'text' => $title,
                        'font' => [
                            'size' => 12,
                            'family' => 'Arial'
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'font' => [
                                'size' => 9,
                                'family' => 'Arial'
                            ]
                        ]
                    ],
                    'x' => [
                        'ticks' => [
                            'font' => [
                                'size' => 9,
                                'family' => 'Arial'
                            ]
                        ]
                    ]
                ],
                'responsive' => true,
                'maintainAspectRatio' => false
            ]
        ];
        
        return $this->getChartImage($chartConfig, 400, 250);
    }
    
    private function generateLineChart($labels, $data, $color, $title, $yAxisLabel)
    {
        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => $yAxisLabel,
                    'data' => $data,
                    'borderColor' => $color,
                    'backgroundColor' => $this->hexToRgba($color, 0.1),
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 2,
                    'pointBackgroundColor' => $color,
                    'borderWidth' => 2
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'display' => false
                    ],
                    'title' => [
                        'display' => true,
                        'text' => $title,
                        'font' => [
                            'size' => 12,
                            'family' => 'Arial'
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => false,
                        'title' => [
                            'display' => true,
                            'text' => $yAxisLabel,
                            'font' => [
                                'size' => 10,
                                'family' => 'Arial'
                            ]
                        ],
                        'ticks' => [
                            'font' => [
                                'size' => 9,
                                'family' => 'Arial'
                            ]
                        ]
                    ],
                    'x' => [
                        'ticks' => [
                            'font' => [
                                'size' => 9,
                                'family' => 'Arial'
                            ]
                        ]
                    ]
                ],
                'responsive' => true,
                'maintainAspectRatio' => false
            ]
        ];
        
        return $this->getChartImage($chartConfig, 400, 250);
    }

    private function generateMultiLineChart($labels, $datasets, $title, $yAxisLabel)
    {
        $chartDatasets = [];
        
        foreach ($datasets as $dataset) {
            $chartDatasets[] = [
                'label' => $dataset['label'],
                'data' => $dataset['data'],
                'borderColor' => $dataset['color'],
                'backgroundColor' => $this->hexToRgba($dataset['color'], 0.1),
                'fill' => false,
                'tension' => 0.3,
                'pointRadius' => 2,
                'pointBackgroundColor' => $dataset['color'],
                'borderWidth' => 2
            ];
        }
        
        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => $chartDatasets
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                        'labels' => [
                            'font' => [
                                'size' => 10,
                                'family' => 'Arial'
                            ]
                        ]
                    ],
                    'title' => [
                        'display' => true,
                        'text' => $title,
                        'font' => [
                            'size' => 12,
                            'family' => 'Arial'
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => false,
                        'title' => [
                            'display' => true,
                            'text' => $yAxisLabel,
                            'font' => [
                                'size' => 10,
                                'family' => 'Arial'
                            ]
                        ],
                        'ticks' => [
                            'font' => [
                                'size' => 9,
                                'family' => 'Arial'
                            ]
                        ]
                    ],
                    'x' => [
                        'ticks' => [
                            'font' => [
                                'size' => 9,
                                'family' => 'Arial'
                            ]
                        ]
                    ]
                ],
                'responsive' => true,
                'maintainAspectRatio' => false
            ]
        ];
        
        return $this->getChartImage($chartConfig, 400, 300);
    }
    
    /**
     * Get QuickChart.io ghaph image
     */
    private function getChartImage($config, $width = 400, $height = 300)
    {
        try {
            $url = 'https://quickchart.io/chart';
            
            // Image settings
            $params = [
                'c' => json_encode($config),
                'width' => $width,
                'height' => $height,
                'backgroundColor' => 'white',
                'format' => 'png',
                'devicePixelRatio' => 1.0
            ];
            
            $response = Http::timeout(30)->get($url, $params);
            
            if ($response->successful()) {
                $imageData = $response->body();
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
            
        } catch (\Exception $e) {
            Log::warning('QuickChart request failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Convert HEX to RGBA
     */
    private function hexToRgba($hex, $alpha = 1.0)
    {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return "rgba($r, $g, $b, $alpha)";
    }
    
    /**
     * Get health data
     */
    private function getAllHealthData($userId, $range)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return null;
            }
            
            $liveStatus = $this->healthService->getLiveStatus($userId);
            $aggregatedStats = $this->healthService->getAggregatedStats($userId, $range);
            $chartData = $this->healthService->getChartData($userId, $range);
            
            if (empty($aggregatedStats) || $aggregatedStats['records_count'] == 0) {
                return null;
            }
            
            $insights = $this->generateInsights($aggregatedStats);
            
            return [
                'user' => $user,
                'liveStatus' => $liveStatus,
                'stats' => $aggregatedStats,
                'chartData' => $chartData,
                'insights' => $insights,
                'range' => $range,
                'exportDate' => now()->format('d/m/Y H:i'),
                'periodLabel' => $this->getPeriodLabel($range),
                'hasData' => true,
            ];
            
        } catch (\Exception $e) {
            Log::error('Health data fetch error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mock data
     */
    private function getEmptyData($range)
    {
        return [
            'user' => Auth::user(),
            'liveStatus' => [
                'mode' => 'No Data',
                'height_cm' => 0,
                'last_adjusted' => 'Never',
            ],
            'stats' => [
                'sitting_pct' => 0,
                'standing_pct' => 0,
                'active_hours' => 0,
                'breaks_per_day' => 0,
                'calories_per_day' => 0,
                'avg_height_mm' => 0,
                'records_count' => 0,
                'sitting_hours' => 0,
                'standing_hours' => 0,
                'avg_sit_height_cm' => 0,
                'avg_stand_height_cm' => 0,
                'total_activations' => 0,
                'total_sit_stand' => 0,
                'error_count' => 0,
            ],
            'chartData' => [
                'labels' => [],
                'sitting_hours' => [],
                'standing_hours' => [],
                'posture_scores' => [],
                'avg_sit_heights' => [],
                'avg_stand_heights' => [],
            ],
            'insights' => [[
                'title' => 'No Data Available',
                'message' => 'No health data was found for the selected period.'
            ]],
            'range' => $range,
            'exportDate' => now()->format('d/m/Y H:i'),
            'periodLabel' => $this->getPeriodLabel($range),
            'hasData' => false,
        ];
    }
    
    private function generateInsights($stats)
    {
        $insights = [];
        
        $sitting = $stats['sitting_pct'] ?? 65;
        $activeHours = $stats['active_hours'] ?? 0;
        $breaks = $stats['breaks_per_day'] ?? 0;
        
        if ($sitting > 60) {
            $insights[] = [
                'title' => 'Posture Balance',
                'message' => "Try standing a bit more to reach a balanced posture! Aim for short standing breaks every hour."
            ];
        } else {
            $insights[] = [
                'title' => 'Great Posture',
                'message' => "Nice balance between sitting and standing — keep it up!"
            ];
        }
        
        if ($activeHours < 6) {
            $insights[] = [
                'title' => 'Increase Activity',
                'message' => "Your active hours are below 6h. Consider micro-activity breaks to raise daily activity."
            ];
        } else {
            $insights[] = [
                'title' => 'Active Time',
                'message' => "You have a good amount of active desk time. Maintain regular breaks."
            ];
        }
        
        if ($breaks < 2) {
            $insights[] = [
                'title' => 'Take Breaks',
                'message' => "You might benefit from more short breaks — try 3–5 minute breaks each hour."
            ];
        }
        
        return $insights;
    }
    
    private function getPeriodLabel($range)
    {
        switch($range) {
            case 'today': return 'Today';
            case 'weekly': return 'Last Week';
            case 'monthly': return 'Last Month';
            case 'yearly': return 'Last Year';
            default: return ucfirst($range);
        }
    }
}