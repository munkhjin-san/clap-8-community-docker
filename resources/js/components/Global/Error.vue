<template>
    <div class="error">
        <p>アプリが更新された直後か、一時的な不整合が発生しています。<br>ページを更新してください。</p>
        <div style="margin-top: 10px;text-align: center;">
            <a href="">今すぐ更新</a>
        </div>
    </div>    
</template>
<style scoped>
.error {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--background-color);
    color: var(--primary-color);
    padding: 20px;
    border-radius: 5px;
    z-index: 5000;
    font-size: 13px;
    line-height: 1.5;
    border: 1px solid tomato;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    max-width: 80%;
    width: max-content;
}
</style>