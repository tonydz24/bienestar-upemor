<?php

namespace Tests\Unit;

use App\Services\Reportes\ReporteGraficaService;
use Tests\TestCase;

class ReporteGraficaServiceTest extends TestCase
{
    protected ReporteGraficaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReporteGraficaService();
    }

    public function test_generar_barras_horizontales_svg_returns_valid_svg(): void
    {
        $datos = [
            ['emocion_nombre' => 'Alegría', 'total' => 12, 'porcentaje' => 60.0, 'codigo_color' => '#f59e0b'],
            ['emocion_nombre' => 'Calma', 'total' => 8, 'porcentaje' => 40.0, 'codigo_color' => '#10b981'],
        ];

        $svg = $this->service->generarBarrasHorizontalesSvg($datos, 500, 30);

        $this->assertStringStartsWith('<svg', trim($svg));
        $this->assertStringEndsWith('</svg>', trim($svg));
        $this->assertStringContainsString('Alegría', $svg);
        $this->assertStringContainsString('Calma', $svg);
        $this->assertStringContainsString('#f59e0b', $svg);
        $this->assertStringContainsString('#10b981', $svg);
        $this->assertStringContainsString('12 (60.0%)', $svg);
    }

    public function test_generar_donut_svg_returns_valid_svg(): void
    {
        $datos = [
            ['emocion_nombre' => 'Ansiedad', 'total' => 5, 'porcentaje' => 50.0, 'codigo_color' => '#f97316'],
            ['emocion_nombre' => 'Estrés', 'total' => 5, 'porcentaje' => 50.0, 'codigo_color' => '#8b5cf6'],
        ];

        $svg = $this->service->generarDonutSvg($datos, 200);

        $this->assertStringStartsWith('<svg', trim($svg));
        $this->assertStringEndsWith('</svg>', trim($svg));
        $this->assertStringContainsString('<path', $svg);
        $this->assertStringContainsString('#f97316', $svg);
        $this->assertStringContainsString('#8b5cf6', $svg);
    }

    public function test_generar_graficas_with_empty_dataset_renders_gracefully(): void
    {
        $svgBarras = $this->service->generarBarrasHorizontalesSvg([]);
        $svgDonut = $this->service->generarDonutSvg([]);

        $this->assertStringContainsString('<svg', $svgBarras);
        $this->assertStringContainsString('Sin datos', $svgBarras);

        $this->assertStringContainsString('<svg', $svgDonut);
        $this->assertStringContainsString('Sin datos', $svgDonut);
    }
}

