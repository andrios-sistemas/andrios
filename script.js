const startBtn = document.getElementById('start-btn');
const captureBtn = document.getElementById('capture-btn');
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const capturedImg = document.getElementById('captured-img');
const constraints = { video: { facingMode: 'environment' } };

startBtn.addEventListener('click', () => {
    navigator.mediaDevices.getUserMedia(constraints)
        .then((stream) => {
            video.srcObject = stream;
            video.onloadedmetadata = () => {
                video.play();
                startBtn.style.display = 'none';
                captureBtn.style.display = 'block';
                video.style.display = 'block';
            };
        })
        .catch((error) => {
            console.error('Erro ao acessar a câmera:', error);
        });
});

captureBtn.addEventListener('click', () => {
    // Após clicar em "Capturar Foto", captura a imagem e exibe
    captureImage();
});

// Função para capturar a imagem
function captureImage() {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageURL = canvas.toDataURL('image/png');
    capturedImg.src = imageURL;
    capturedImg.style.display = 'block';
    
    // Oculta o vídeo e exibe novamente o botão de captura
    video.style.display = 'none';
    captureBtn.style.display = 'none';
    startBtn.style.display = 'block';
}
