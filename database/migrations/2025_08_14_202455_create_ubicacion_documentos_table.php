<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('ubicacion_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->cascadeOnDelete();

            // Generales
            $table->boolean('doc_libre_deuda_municipal')->default(false);
            $table->boolean('doc_planeamiento_urbano')->default(false);
            $table->boolean('doc_solicitud_habilitacion_pago')->default(false);

            // Físicas
            $table->boolean('doc_afip_constancia_fisica')->default(false);
            $table->boolean('doc_fotocopia_dni')->default(false);
            $table->boolean('doc_constancia_recaudacion')->default(false);

            // Jurídicas
            $table->boolean('doc_afip_constancia_juridica')->default(false);
            $table->boolean('doc_acta_constitucion')->default(false);
            $table->boolean('doc_contrato_societario')->default(false);
            $table->boolean('doc_docs_representantes')->default(false);

            // Otros
            $table->boolean('doc_comprobante_uso_local')->default(false);

            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('ubicacion_documentos');
    }
};
