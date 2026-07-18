<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit;
}

$usuario = $_SESSION['usuario_activo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GESTIÓN ACADÉMICA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
    <style>
        .btn-verde {background-color: var(--verde-oscuro); color: white;}
        .btn-verde:hover {background-color: var(--verde-medio); color: white;}
        .table-actions button { margin-right: 5px; }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'backend/includes/sidebar.php'; ?>

        <div id="content" class="w-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <div class="container-fluid d-flex justify-content-between">
                    <span class="navbar-brand mb-0 h4 text-secondary">MÓDULO: GESTIÓN ACADÉMICA</span>
                    
                    <div>
                        <span class="me-4 fw-bold" style="color:var(--verde-oscuro)">
                            👤 <?php echo strtoupper($usuario['nombre']) . ' | Rol: ' . ucfirst($usuario['rol']); ?>
                        </span>
                        <a href="backend/includes/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar Sesión</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between mb-4">
                    <div class="d-flex gap-2 w-50">
                        <input type="text" id="input-busqueda" class="form-control" placeholder="🔎 Buscar estudiante por matrícula o nombre">
                        <button class="btn btn-outline-secondary">Buscar</button>
                    </div>
                    <button class="btn btn-verde" onclick="abrirModal()">+ Registrar Estudiante</button>
                </div>
                
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Matrícula</th>
                                    <th>Nombre Completo</th>
                                    <th>Curso / Grado</th>
                                    <th>Promedio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo-tabla-academica">
                                <!-- Datos estáticos de ejemplo para la interfaz -->
                                <tr>
                                    <td>MAT-202601</td>
                                    <td>Ana Sofía Martínez</td>
                                    <td>1ro de Secundaria</td>
                                    <td>9.5</td>
                                    <td><span class="badge bg-success">Activo</span></td>
                                    <td class="table-actions">
                                        <button class="btn btn-sm btn-warning text-dark" onclick="abrirModal(true)">Editar</button>
                                        <button class="btn btn-sm btn-danger">Eliminar</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>MAT-202602</td>
                                    <td>Carlos Pérez Gómez</td>
                                    <td>3ro de Secundaria</td>
                                    <td>7.8</td>
                                    <td><span class="badge bg-success">Activo</span></td>
                                    <td class="table-actions">
                                        <button class="btn btn-sm btn-warning text-dark" onclick="abrirModal(true)">Editar</button>
                                        <button class="btn btn-sm btn-danger">Eliminar</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>MAT-202603</td>
                                    <td>Lucía Fernández</td>
                                    <td>6to de Primaria</td>
                                    <td>5.4</td>
                                    <td><span class="badge bg-danger">En Riesgo</span></td>
                                    <td class="table-actions">
                                        <button class="btn btn-sm btn-warning text-dark" onclick="abrirModal(true)">Editar</button>
                                        <button class="btn btn-sm btn-danger">Eliminar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal para el formulario CRUD -->
    <div class="modal fade" id="modalAcademico" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formAcademico" onsubmit="guardarRegistro(event)">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="modalTitulo">Registrar Nuevo Estudiante</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="estudiante-id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Matrícula</label>
                            <input type="text" id="estudiante-matricula" class="form-control" required pattern="[A-Za-z0-9\-]+" placeholder="Ej. MAT-12345">
                            <div class="invalid-feedback">Por favor ingrese una matrícula válida (sin espacios).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre Completo</label>
                            <input type="text" id="estudiante-nombre" class="form-control" required minlength="3" placeholder="Nombres y Apellidos">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Curso / Grado</label>
                            <select id="estudiante-curso" class="form-select" required>
                                <option value="" disabled selected>Seleccione un curso</option>
                                <option value="1ro Primaria">1ro Primaria</option>
                                <option value="6to Primaria">6to Primaria</option>
                                <option value="1ro Secundaria">1ro Secundaria</option>
                                <option value="3ro Secundaria">3ro Secundaria</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Promedio Actual</label>
                            <input type="number" id="estudiante-promedio" class="form-control" step="0.1" min="0" max="10" required placeholder="0.0 - 10.0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Estado</label>
                            <select id="estudiante-estado" class="form-select" required>
                                <option value="Activo">Activo</option>
                                <option value="En Riesgo">En Riesgo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-verde">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (necesario para el Modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalAcademico = new bootstrap.Modal(document.getElementById('modalAcademico'));

        function abrirModal(esEdicion = false) {
            const form = document.getElementById('formAcademico');
            form.reset();
            form.classList.remove('was-validated');

            if(esEdicion) {
                document.getElementById('modalTitulo').innerText = 'Editar Estudiante';
                // Aquí se llenarían los datos mediante JS (simulado)
            } else {
                document.getElementById('modalTitulo').innerText = 'Registrar Nuevo Estudiante';
            }
            modalAcademico.show();
        }

        function guardarRegistro(event) {
            event.preventDefault();
            const form = event.target;
            
            if (!form.checkValidity()) {
                event.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            // Simulamos guardado exitoso
            alert('Registro guardado correctamente (Modo Interfaz)');
            modalAcademico.hide();
        }
    </script>
</body>
</html>
