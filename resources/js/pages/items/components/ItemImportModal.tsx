import { useState, useRef } from 'react';
import { router } from '@inertiajs/react';
import {
    Upload,
    FileSpreadsheet,
    AlertCircle,
    CheckCircle2,
    X,
    Loader2,
    AlertTriangle,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import items from '@/routes/items';

interface ItemImportModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    companyId: string;
}

interface ImportResult {
    success: boolean;
    created: number;
    updated: number;
    skipped: number;
    total: number;
    errors: string[];
}

function getCsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export function ItemImportModal({
    open,
    onOpenChange,
    companyId,
}: ItemImportModalProps) {
    const [file, setFile] = useState<File | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);
    const [result, setResult] = useState<ImportResult | null>(null);

    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleReset = () => {
        setFile(null);
        setErrorMsg(null);
        setResult(null);
        setIsUploading(false);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleClose = () => {
        if (result && (result.created > 0 || result.updated > 0)) {
            router.reload();
        }
        handleReset();
        onOpenChange(false);
    };

    const handleFileSelected = (selected: File | null) => {
        setErrorMsg(null);
        setResult(null);
        if (!selected) {
            setFile(null);
            return;
        }

        const validExts = ['.xlsx', '.xls', '.csv'];
        const fileName = selected.name.toLowerCase();
        const isValid = validExts.some((ext) => fileName.endsWith(ext));

        if (!isValid) {
            setErrorMsg('El archivo debe tener formato .xlsx, .xls o .csv');
            return;
        }

        if (selected.size > 10 * 1024 * 1024) {
            setErrorMsg(
                'El archivo excede el tamaño máximo permitido de 10 MB',
            );
            return;
        }

        setFile(selected);
    };

    const handleSubmit = async () => {
        if (!file) return;

        setIsUploading(true);
        setErrorMsg(null);

        const formData = new FormData();
        formData.append('file', file);

        try {
            const csrf = getCsrfToken();
            const headers: Record<string, string> = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            };
            if (csrf) {
                headers['X-XSRF-TOKEN'] = csrf;
            }

            const response = await fetch(items.import(companyId).url, {
                method: 'POST',
                headers,
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                const message =
                    data?.message ||
                    data?.errors?.file?.[0] ||
                    'Ocurrió un error al procesar el archivo.';
                setErrorMsg(message);
            } else {
                setResult(data as ImportResult);
            }
        } catch {
            setErrorMsg(
                'Error de red o conexión al servidor al subir el archivo.',
            );
        } finally {
            setIsUploading(false);
        }
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
                    handleClose();
                } else {
                    onOpenChange(true);
                }
            }}
        >
            <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-xl font-bold">
                        <FileSpreadsheet className="h-6 w-6 text-primary" />
                        Importar Artículos desde Excel
                    </DialogTitle>
                    <DialogDescription>
                        Carga masiva con lógica <strong>UPSERT por SKU</strong>.
                        Si el SKU ya existe en la empresa se actualizará; si no
                        existe, se creará un artículo nuevo.
                    </DialogDescription>
                </DialogHeader>

                {/* Si no hay resultados todavía, mostrar formulario de carga */}
                {!result ? (
                    <div className="space-y-4 py-2">
                        {errorMsg && (
                            <div className="flex items-start gap-2.5 rounded-lg border border-red-200 bg-red-50 p-3.5 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300">
                                <AlertCircle className="h-5 w-5 shrink-0 text-red-600 dark:text-red-400" />
                                <div>{errorMsg}</div>
                            </div>
                        )}

                        <div
                            onDragOver={(e) => {
                                e.preventDefault();
                                setIsDragging(true);
                            }}
                            onDragLeave={(e) => {
                                e.preventDefault();
                                setIsDragging(false);
                            }}
                            onDrop={(e) => {
                                e.preventDefault();
                                setIsDragging(false);
                                if (e.dataTransfer.files?.[0]) {
                                    handleFileSelected(e.dataTransfer.files[0]);
                                }
                            }}
                            onClick={() => fileInputRef.current?.click()}
                            className={`flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed p-8 transition-colors ${
                                isDragging
                                    ? 'border-primary bg-primary/5'
                                    : 'border-muted-foreground/25 hover:border-primary/60 hover:bg-muted/40'
                            }`}
                        >
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".xlsx, .xls, .csv"
                                className="hidden"
                                onChange={(e) => {
                                    handleFileSelected(
                                        e.target.files?.[0] ?? null,
                                    );
                                }}
                            />

                            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <Upload className="h-7 w-7" />
                            </div>

                            <p className="mt-3 text-base font-semibold">
                                Arrastra tu archivo Excel aquí o haz clic para
                                seleccionarlo
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Formatos soportados: .xlsx, .xls, .csv (Máximo
                                10 MB)
                            </p>
                        </div>

                        {file && (
                            <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-3.5">
                                <div className="flex items-center gap-3 overflow-hidden">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                        <FileSpreadsheet className="h-5 w-5" />
                                    </div>
                                    <div className="overflow-hidden text-left">
                                        <p className="truncate text-sm font-semibold text-foreground">
                                            {file.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {(file.size / 1024).toFixed(1)} KB
                                        </p>
                                    </div>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        handleReset();
                                    }}
                                    disabled={isUploading}
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                        )}

                        <div className="rounded-lg bg-amber-500/10 p-3.5 text-xs text-amber-800 dark:text-amber-300">
                            <p className="mb-1 flex items-center gap-1.5 font-semibold">
                                <AlertTriangle className="h-4 w-4" />
                                Recomendación antes de importar:
                            </p>
                            <ul className="list-disc space-y-0.5 pl-4">
                                <li>
                                    Asegúrate de que la primera columna contenga
                                    el <strong>SKU</strong>.
                                </li>
                                <li>
                                    Si quieres actualizar precios rápidamente,
                                    descarga primero la plantilla con artículos
                                    actuales.
                                </li>
                                <li>
                                    Para nuevos artículos, el{' '}
                                    <strong>Nombre</strong> es obligatorio.
                                </li>
                            </ul>
                        </div>
                    </div>
                ) : (
                    /* Pantalla de resultados */
                    <div className="space-y-4 py-2">
                        <div className="grid grid-cols-3 gap-3">
                            <div className="rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3.5 text-center">
                                <p className="text-xs font-medium text-emerald-700 dark:text-emerald-400">
                                    Actualizados
                                </p>
                                <p className="text-2xl font-bold text-emerald-800 dark:text-emerald-300">
                                    {result.updated}
                                </p>
                            </div>
                            <div className="rounded-lg border border-blue-500/20 bg-blue-500/10 p-3.5 text-center">
                                <p className="text-xs font-medium text-blue-700 dark:text-blue-400">
                                    Nuevos creados
                                </p>
                                <p className="text-2xl font-bold text-blue-800 dark:text-blue-300">
                                    {result.created}
                                </p>
                            </div>
                            <div className="rounded-lg border border-amber-500/20 bg-amber-500/10 p-3.5 text-center">
                                <p className="text-xs font-medium text-amber-700 dark:text-amber-400">
                                    Filas con error
                                </p>
                                <p className="text-2xl font-bold text-amber-800 dark:text-amber-300">
                                    {result.skipped}
                                </p>
                            </div>
                        </div>

                        {result.errors && result.errors.length > 0 && (
                            <div className="space-y-2">
                                <p className="flex items-center gap-1 text-xs font-semibold text-red-600 dark:text-red-400">
                                    <AlertCircle className="h-4 w-4" />
                                    Detalle de filas no procesadas (
                                    {result.errors.length}):
                                </p>
                                <div className="max-h-48 overflow-y-auto rounded-lg border border-red-200 bg-red-50/50 p-3 text-xs text-red-800 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-300">
                                    <ul className="list-disc space-y-1 pl-4">
                                        {result.errors.map((err, idx) => (
                                            <li key={idx}>{err}</li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        )}

                        {result.errors.length === 0 && (
                            <div className="flex items-center gap-2 rounded-lg bg-emerald-500/10 p-3 text-sm text-emerald-800 dark:text-emerald-300">
                                <CheckCircle2 className="h-5 w-5 text-emerald-600" />
                                <span>
                                    ¡Excelente! Todas las filas se procesaron
                                    sin ningún error.
                                </span>
                            </div>
                        )}
                    </div>
                )}

                <DialogFooter className="gap-2 sm:gap-0">
                    {!result ? (
                        <>
                            <Button
                                variant="outline"
                                onClick={handleClose}
                                disabled={isUploading}
                            >
                                Cancelar
                            </Button>
                            <Button
                                onClick={handleSubmit}
                                disabled={!file || isUploading}
                                className="font-semibold"
                            >
                                {isUploading ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Procesando archivo...
                                    </>
                                ) : (
                                    <>
                                        <Upload className="mr-2 h-4 w-4" />
                                        Procesar archivo
                                    </>
                                )}
                            </Button>
                        </>
                    ) : (
                        <>
                            <Button variant="outline" onClick={handleReset}>
                                Subir otro archivo
                            </Button>
                            <Button
                                onClick={handleClose}
                                className="font-semibold"
                            >
                                Finalizar y ver artículos
                            </Button>
                        </>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
