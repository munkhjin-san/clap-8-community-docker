<template>
    <div class="error">
        <p>エラーが発生しました。<br>
        ページを更新してください。</p>
        <a href="/" >ページを更新</a>
    </div>
</template>
<style scoped>
.error {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: tomato;
    color: #fff;
    padding: 20px;
    border-radius: 5px;
    z-index: 1000;
    font-size: 14px;
    line-height: 2;
}
</style>