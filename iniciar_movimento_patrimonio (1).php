<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Movimentação de Patrimônio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js"></script>
    
    <style>
       video#camera-preview {
            max-width: 100%; 
            height: auto; 
            border-radius: 8px;
            margin-top: 10px;
        }

        /* Estilo para o loader */
        #loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Fundo semi-transparente */
            display: none; /* Inicialmente oculto */
            z-index: 9999; /* Z-index alto para garantir que o loader fique acima de outros elementos */
        }

        #loader {
            border: 16px solid #f3f3f3; /* Light grey */
            border-top: 16px solid #FF0000; /* vermelho */
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite; /* Animação de rotação */
            position: absolute;
            top: 50%;
            left: 50%;
            margin-top: -60px;
            margin-left: -60px;
        }

        #loader-text {
            color: white;
            font-size: 18px;
            text-align: center;
            margin-top: 20px;
        }

        #loader-info {
            color: white;
            font-size: 16px;
            text-align: center;
            margin-top: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <h2>Movimentação de Patrimônio - Iniciar</h2>
                <form action="processar_movimento.php" method="post" id="formMovimentacao">
                    <div class="mt-3">
                        <label id="label-result" class="mb-2"></label> <!-- Nova label para mostrar a segunda parte do QR code -->       
                    </div>
                    <div class="form-group">
                        <label for="id_patrimonio">Patrimônio:</label>
                        <input type="text" class="form-control" id="id_patrimonio" name="id_patrimonio" placeholder="Identificação do patrimônio" required>
                    </div>
                    <button id="start-scan-btn" style="display: block;" class="btn btn-primary">Ler QR Code</button>
                    <button id="close-camera-btn" style="display: none;" class="btn btn-danger">Fechar Câmera</button>
                    <br>
                    <div class="mt-3">
                        <video id="camera-preview" playsinline style="display: none;"></video>
                    </div>
                    <canvas id="qr-canvas" style="display: none;"></canvas>
                    <div class="form-group">
                        <label for="cd_local_atual">Local Atual:</label>
                        <select class="form-control" id="cd_local_atual" name="cd_local_atual" placeholder="Local atual do patrimônio" required>
                            <!-- Aqui o local atual será preenchido automaticamente -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="searchLocalDestino">Pesquisar Local de Destino:</label>
                        <input type="text" class="form-control" id="searchLocalDestino" placeholder="Digite para pesquisar">
                    </div>
                    <div class="form-group">
                        <label for="cd_local">Local de Destino:</label>
                        <button id="start-scan-dest-btn" style="display: block;" class="btn btn-primary">Ler QR Code do Local de Destino</button>
                        <button id="close-camera-dest-btn" style="display: none;" class="btn btn-danger">Fechar Câmera do Local de Destino</button>
                        <select class="form-control" id="cd_local" name="cd_local" placeholder="Local de destino do patrimônio" required>
                            <option value=""></option>
                            <?php
                            $sql = "SELECT cd_local FROM local WHERE cd_local='inserir aqui o código lido pelo QRcode';";
                            $resultado = mysqli_query($con, $sql);
                            while ($row = mysqli_fetch_assoc($resultado)) {
                                echo '<option value="' . $row['cd_local'] . '">Cód: ' . $row['cd_local'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Gravar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function(){
            // Função para iniciar a leitura do QR code do Local de Destino
            function startDestQRScan() {
                const startScanBtn = document.getElementById('start-scan-dest-btn');
                const closeCameraBtn = document.getElementById('close-camera-dest-btn');
                const cameraPreview = document.getElementById('camera-preview');
                let stream = null;

                startScanBtn.addEventListener('click', function() {
                    const constraints = { video: { facingMode: "environment" } };

                    navigator.mediaDevices.getUserMedia(constraints)
                        .then(function(s) {
                            stream = s;
                            cameraPreview.srcObject = stream;
                            cameraPreview.onloadedmetadata = function(e) {
                                cameraPreview.play();
                                cameraPreview.style.display = 'block';
                                closeCameraBtn.style.display = 'block'; // Mostrar botão para fechar a câmera
                                startScanBtn.style.display = 'none';
                                scanQRCode(); // Iniciar a leitura do QR code automaticamente
                            };
                        })
                        .catch(function(error) {
                            console.error('Erro ao acessar câmera:', error);
                            alert('Erro ao acessar câmera: ' + error);
                        });
                });

                closeCameraBtn.addEventListener('click', function() {
                    if (stream) {
                        const tracks = stream.getTracks();
                        tracks.forEach(track => track.stop());
                        cameraPreview.style.display = 'none'; // Esconder o vídeo.
                        closeCameraBtn.style.display = 'none'; // Esconder o botão para fechar a câmera.
                        startScanBtn.style.display = 'block'; // Mostrar botão para nova leitura.
                    }
                });

                // Função para verificar continuamente se um QR code foi detectado no Local de Destino
                function scanQRCode() {
                    const ctx = document.createElement('canvas').getContext('2d');
                    const qrCanvas = document.createElement('canvas');
                    qrCanvas.width = cameraPreview.videoWidth;
                    qrCanvas.height = cameraPreview.videoHeight;

                    const scanInterval = setInterval(function() {
                        ctx.drawImage(cameraPreview, 0, 0, qrCanvas.width, qrCanvas.height);
                        const imageData = ctx.getImageData(0, 0, qrCanvas.width, qrCanvas.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height);

                        if (code) {
                            const cd_local = code.data.trim(); // Valor lido do QR code
                            // Atualizar o SELECT com o valor lido
                            $('#cd_local').val(cd_local);

                            // Fechar a câmera após o QR code ser lido
                            if (stream) {
                                const tracks = stream.getTracks();
                                tracks.forEach(track => track.stop());
                                clearInterval(scanInterval); // Parar a verificação contínua
                                cameraPreview.style.display = 'none'; // Esconder o vídeo.
                                closeCameraBtn.style.display = 'none'; // Esconder o botão para fechar a câmera.
                                startScanBtn.style.display = 'block'; // Mostrar botão para nova leitura.
                            }
                        }
                    }, 100); // Verificar a cada 100ms
                }
            }

            // Iniciar a leitura do QR code do Local de Destino automaticamente
            startDestQRScan();
        });
    </script>
</body>
</html>
