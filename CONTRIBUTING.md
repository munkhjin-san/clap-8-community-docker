# コントリビューションガイド

MISOプロジェクトへの貢献に興味を持っていただき、ありがとうございます！このガイドでは、プロジェクトに貢献する方法について説明します。

## 🤝 コントリビューションの方法

### バグレポート
バグを発見した場合は、以下の情報を含めてIssueを作成してください：

- **バグの説明**: 何が起こったかの簡潔な説明
- **再現手順**: バグを再現するための詳細な手順
- **期待される動作**: 本来どのように動作すべきか
- **実際の動作**: 実際に何が起こったか
- **環境情報**: OS、ブラウザ、PHPバージョンなど
- **スクリーンショット**: 可能であれば画面キャプチャ

### 機能提案
新機能の提案は以下の情報を含めてください：

- **機能の概要**: 提案する機能の簡潔な説明
- **用途**: なぜその機能が必要なのか
- **詳細な仕様**: 機能の詳細な動作
- **代替案**: 他に考えられる解決方法

### プルリクエスト

#### 開発環境のセットアップ
1. このリポジトリをフォーク
2. ローカルにクローン:
   ```bash
   git clone https://github.com/あなたのユーザー名/clap-8.git
   cd clap-8
   ```
3. 依存関係をインストール:
   ```bash
   composer install
   npm install
   ```
4. 環境設定を行う（README.mdの手順に従って）

#### 開発フロー
1. 新しいブランチを作成:
   ```bash
   git checkout -b feature/新機能名
   # または
   git checkout -b fix/バグ修正名
   ```

2. 変更を行い、コミット:
   ```bash
   git add .
   git commit -m "feat: 新機能の追加"
   ```

3. コードの品質チェック:
   ```bash
   # PHPコード
   composer run-script phpcs
   composer run-script phpstan
   
   # JavaScript/TypeScript
   npm run lint
   npm run type-check
   ```

4. テストの実行:
   ```bash
   # PHP
   php artisan test
   
   # JavaScript
   npm run test
   ```

5. プッシュしてプルリクエストを作成:
   ```bash
   git push origin feature/新機能名
   ```

## 📝 コーディング規約

### PHP/Laravel
- [PSR-12](https://www.php-fig.org/psr/psr-12/)に準拠
- Laravelのベストプラクティスに従う
- 意味のある変数名・関数名を使用
- 複雑なロジックにはコメントを追加

### JavaScript/TypeScript
- ESLintとPrettierの設定に従う
- TypeScriptの型を適切に使用
- 関数とクラスにJSDocコメントを追加

### Vue.js
- Composition APIを優先的に使用
- Single File Componentの構造を維持
- Props、Emits、Exposedを明確に定義

### CSS
- Tailwind CSSのユーティリティクラスを優先使用
- カスタムCSSは最小限に留める
- レスポンシブデザインを考慮

## 🧪 テスト

### テストの種類
- **Unit Tests**: 個別の関数やクラスのテスト
- **Feature Tests**: アプリケーションの機能テスト
- **E2E Tests**: エンドツーエンドテスト

### テスト実行
```bash
# 全てのテスト
php artisan test

# 特定のテストファイル
php artisan test tests/Feature/UserTest.php

# カバレッジレポート
php artisan test --coverage
```

## 📊 コミット規約

[Conventional Commits](https://www.conventionalcommits.org/)に従ってください：

- `feat:` 新機能
- `fix:` バグ修正
- `docs:` ドキュメント更新
- `style:` コードフォーマット
- `refactor:` リファクタリング
- `perf:` パフォーマンス改善
- `test:` テスト追加・修正
- `chore:` ビルド・ツール設定

例：
```
feat(chat): リアルタイムメッセージング機能を追加

- Pusherを使用したリアルタイム通信
- メッセージの既読機能
- ファイル添付サポート
```

## 🔍 プルリクエストレビュー

プルリクエストは以下の観点でレビューされます：

### コード品質
- [ ] コーディング規約に準拠
- [ ] 適切なエラーハンドリング
- [ ] セキュリティ問題がない
- [ ] パフォーマンスに問題がない

### 機能性
- [ ] 仕様通りに動作する
- [ ] エッジケースを考慮
- [ ] ユーザビリティが良い
- [ ] アクセシビリティを考慮

### テスト
- [ ] 適切なテストが追加されている
- [ ] 既存のテストが通る
- [ ] テストカバレッジが十分

### ドキュメント
- [ ] 必要に応じてドキュメントが更新されている
- [ ] コメントが適切に追加されている

## 💡 貢献のアイデア

### 初心者向け
- タイポ修正
- ドキュメントの改善
- UI/UXの小さな改善
- 翻訳の追加・修正

### 中級者向け
- 新しいVueコンポーネントの作成
- API エンドポイントの追加
- パフォーマンス最適化
- アクセシビリティ改善

### 上級者向け
- 新機能の設計・実装
- アーキテクチャの改善
- セキュリティ強化
- 大規模リファクタリング

## 🛡️ セキュリティ

セキュリティ上の脆弱性を発見した場合は、公開のIssueではなく、直接開発チームに連絡してください：

- Email: security@clap-platform.com
- 件名: [SECURITY] セキュリティ問題の報告

## 📞 サポート

質問や不明な点がある場合：

- [GitHub Discussions](https://github.com/tumur1/clap-8/discussions)で質問
- [Wiki](https://github.com/tumur1/clap-8/wiki)でドキュメントを確認
- Email: dev-support@clap-platform.com

## 🎉 謝辞

MISOプロジェクトにコントリビュートしていただき、ありがとうございます！あなたの貢献により、MISOはより良いプラットフォームになります。

---

Happy coding! 🚀
