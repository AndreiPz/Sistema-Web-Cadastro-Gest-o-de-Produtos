<?php
require __DIR__ . '/config.php';

$hoje = date('Y-m-d');
$mensagemErro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    try {
        if ($acao === 'tipo_salvar') {
            $id = (int)($_POST['codtipo'] ?? 0);
            $nome = trim($_POST['nometipo'] ?? '');
            if ($nome === '' || mb_strlen($nome) > 100) throw new RuntimeException('Informe um tipo com até 100 caracteres.');
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE tipoproduto SET nometipo=? WHERE codtipo=?');
                $stmt->execute([$nome, $id]);
                flash('Categoria atualizada com sucesso.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO tipoproduto (nometipo) VALUES (?)');
                $stmt->execute([$nome]);
                flash('Categoria cadastrada com sucesso.');
            }
        } elseif ($acao === 'tipo_excluir') {
            $id = (int)($_POST['codtipo'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM tipoproduto WHERE codtipo=?');
            $stmt->execute([$id]);
            flash('Categoria excluída com sucesso.');
        } elseif ($acao === 'produto_salvar') {
            $id = (int)($_POST['codproduto'] ?? 0);
            $nome = trim($_POST['nomeproduto'] ?? '');
            $quantidade = str_replace(',', '.', trim($_POST['quantidade'] ?? ''));
            $valor = str_replace(',', '.', trim($_POST['valor'] ?? ''));
            $validade = $_POST['datavencimento'] ?? '';
            $tipo = (int)($_POST['codtipofk'] ?? 0);

            if ($nome === '' || mb_strlen($nome) > 150) throw new RuntimeException('Informe o nome do produto (até 150 caracteres).');
            if (!is_numeric($quantidade) || (float)$quantidade < 0) throw new RuntimeException('A quantidade deve ser um número igual ou maior que zero.');
            if (!is_numeric($valor) || (float)$valor < 0) throw new RuntimeException('O valor deve ser um número igual ou maior que zero.');
            $dt = DateTime::createFromFormat('Y-m-d', $validade);
            if (!$dt || $dt->format('Y-m-d') !== $validade) throw new RuntimeException('Informe uma data de vencimento válida.');
            $check = $pdo->prepare('SELECT COUNT(*) FROM tipoproduto WHERE codtipo=?');
            $check->execute([$tipo]);
            if (!$check->fetchColumn()) throw new RuntimeException('Selecione uma categoria válida.');

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE produto SET nomeproduto=?, quantidade=?, valor=?, datavencimento=?, codtipofk=? WHERE codproduto=?');
                $stmt->execute([$nome, $quantidade, $valor, $validade, $tipo, $id]);
                $produtoId = $id;
                flash('Produto atualizado com sucesso.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO produto (nomeproduto, quantidade, valor, datavencimento, codtipofk) VALUES (?,?,?,?,?)');
                $stmt->execute([$nome, $quantidade, $valor, $validade, $tipo]);
                $produtoId = (int)$pdo->lastInsertId();
                flash('Produto cadastrado com sucesso.');
            }

            if (!empty($_FILES['fotos']['name'][0])) {
                $pasta = __DIR__ . '/uploads';
                if (!is_dir($pasta)) mkdir($pasta, 0755, true);
                $permitidos = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'image/gif'=>'gif'];
                foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp) {
                    if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                    if ($_FILES['fotos']['error'][$i] !== UPLOAD_ERR_OK) throw new RuntimeException('Falha ao enviar uma das imagens.');
                    if ($_FILES['fotos']['size'][$i] > 5 * 1024 * 1024) throw new RuntimeException('Cada imagem deve ter no máximo 5 MB.');
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                    if (!isset($permitidos[$mime])) throw new RuntimeException('Formato inválido. Use JPG, PNG, WEBP ou GIF.');
                    $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];
                    if (!move_uploaded_file($tmp, $pasta . '/' . $nomeArquivo)) throw new RuntimeException('Não foi possível salvar uma imagem.');
                    $descricao = trim($_FILES['fotos']['name'][$i]) ?: 'Foto do produto';
                    $stmtFoto = $pdo->prepare('INSERT INTO fotos (descricao, caminhofoto, codprodutofk) VALUES (?,?,?)');
                    $stmtFoto->execute([$descricao, 'uploads/' . $nomeArquivo, $produtoId]);
                }
            }
        } elseif ($acao === 'produto_excluir') {
            $id = (int)($_POST['codproduto'] ?? 0);
            $stmt = $pdo->prepare('SELECT caminhofoto FROM fotos WHERE codprodutofk=?');
            $stmt->execute([$id]);
            $caminhos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $pdo->beginTransaction();
            $del = $pdo->prepare('DELETE FROM produto WHERE codproduto=?');
            $del->execute([$id]); // ON DELETE CASCADE remove os registros de fotos
            $pdo->commit();
            foreach ($caminhos as $caminho) {
                $arquivo = __DIR__ . '/' . $caminho;
                if (is_file($arquivo)) @unlink($arquivo);
            }
            flash('Produto e fotos vinculadas excluídos com sucesso.');
        } elseif ($acao === 'foto_excluir') {
            $id = (int)($_POST['codfoto'] ?? 0);
            $stmt = $pdo->prepare('SELECT caminhofoto FROM fotos WHERE codfoto=?');
            $stmt->execute([$id]);
            $caminho = $stmt->fetchColumn();
            if ($caminho) {
                $del = $pdo->prepare('DELETE FROM fotos WHERE codfoto=?');
                $del->execute([$id]);
                $arquivo = __DIR__ . '/' . $caminho;
                if (is_file($arquivo)) @unlink($arquivo);
                flash('Foto removida com sucesso.');
            }
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash($e instanceof PDOException ? 'Não foi possível concluir. Verifique se a categoria não está sendo usada por produtos.' : $e->getMessage(), 'danger');
    }
    redirecionar();
}

$tipos = $pdo->query('SELECT * FROM tipoproduto ORDER BY nometipo')->fetchAll();
$produtos = $pdo->query('SELECT p.*, t.nometipo, (SELECT COUNT(*) FROM fotos f WHERE f.codprodutofk=p.codproduto) AS total_fotos FROM produto p JOIN tipoproduto t ON t.codtipo=p.codtipofk ORDER BY p.nomeproduto')->fetchAll();
$fotosStmt = $pdo->query('SELECT * FROM fotos ORDER BY codfoto');
$fotosPorProduto = [];
foreach ($fotosStmt as $foto) $fotosPorProduto[$foto['codprodutofk']][] = $foto;

$editarProduto = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM produto WHERE codproduto=?');
    $stmt->execute([(int)$_GET['editar']]);
    $editarProduto = $stmt->fetch() ?: null;
}
$editarTipo = null;
if (isset($_GET['editar_tipo'])) {
    $stmt = $pdo->prepare('SELECT * FROM tipoproduto WHERE codtipo=?');
    $stmt->execute([(int)$_GET['editar_tipo']]);
    $editarTipo = $stmt->fetch() ?: null;
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FeiraFácil | Gestão de produtos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
</head>
<body>
<header class="topo">
  <nav class="navbar navbar-expand-lg navbar-dark container">
    <a class="navbar-brand fw-bold" href="index.php">🥬 FeiraFácil</a>
    <span class="text-white-50 small">Gestão simples para sua feira</span>
  </nav>
</header>
<main class="container py-4">
  <section class="hero mb-4">
    <div><span class="eyebrow">CONTROLE DA SUA FEIRA</span><h1>Produtos sempre em ordem.</h1><p>Cadastre produtos, acompanhe o estoque e controle os vencimentos em um só lugar.</p></div>
    <div class="hero-icon">🧺</div>
  </section>

  <?php if ($flash): ?>
    <div class="alert alert-<?=h($flash['tipo'])?> alert-dismissible fade show" role="alert"><?=h($flash['mensagem'])?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <section class="painel">
        <div class="section-heading"><div><span class="eyebrow">ESTOQUE</span><h2>Produtos cadastrados</h2></div><span class="counter"><?=count($produtos)?> produto(s)</span></div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Produto</th><th>Categoria</th><th>Qtd.</th><th>Valor</th><th>Validade</th><th>Fotos</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (!$produtos): ?><tr><td colspan="7" class="text-center py-4 text-secondary">Nenhum produto cadastrado ainda.</td></tr><?php endif; ?>
            <?php foreach ($produtos as $p):
              $dias = (int)floor((strtotime($p['datavencimento']) - strtotime($hoje)) / 86400);
              $status = $dias < 0 ? ['Vencido','danger'] : ($dias <= 7 ? ['Vence em breve','warning'] : ['Dentro da validade','success']);
            ?>
              <tr>
                <td class="fw-semibold"><?=h($p['nomeproduto'])?></td><td><?=h($p['nometipo'])?></td>
                <td><?=h(rtrim(rtrim(number_format((float)$p['quantidade'],2,',','.'),'0'),','))?></td>
                <td><?=h(dinheiro($p['valor']))?></td><td><span class="badge text-bg-<?=$status[1]?>"><?=h($status[0])?></span><div class="small text-secondary"><?=h(dataBR($p['datavencimento']))?></div></td>
                <td><button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#galeria<?=$p['codproduto']?>"><?= (int)$p['total_fotos'] ?> foto(s)</button></td>
                <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="?editar=<?=$p['codproduto']?>#form-produto">Editar</a>
                  <form method="post" class="d-inline" onsubmit="return confirm('Excluir este produto e todas as fotos vinculadas?')"><input type="hidden" name="acao" value="produto_excluir"><input type="hidden" name="codproduto" value="<?=$p['codproduto']?>"><button class="btn btn-sm btn-outline-danger">Excluir</button></form>
                </td>
              </tr>
              <?php if (!empty($fotosPorProduto[$p['codproduto']])): ?>
              <div class="modal fade" id="galeria<?=$p['codproduto']?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Fotos: <?=h($p['nomeproduto'])?></h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div><div class="modal-body">
                <div id="carousel<?=$p['codproduto']?>" class="carousel slide" data-bs-ride="false"><div class="carousel-inner">
                  <?php foreach ($fotosPorProduto[$p['codproduto']] as $i=>$f): ?><div class="carousel-item <?=$i===0?'active':''?>"><img src="<?=h($f['caminhofoto'])?>" class="d-block gallery-image mx-auto" alt="<?=h($f['descricao'])?>"><div class="text-center mt-2"><?=h($f['descricao'])?></div><form method="post" class="text-center mt-2" onsubmit="return confirm('Remover esta foto?')"><input type="hidden" name="acao" value="foto_excluir"><input type="hidden" name="codfoto" value="<?=$f['codfoto']?>"><button class="btn btn-sm btn-outline-danger">Excluir esta foto</button></form></div><?php endforeach; ?>
                </div><?php if (count($fotosPorProduto[$p['codproduto']])>1): ?><button class="carousel-control-prev" type="button" data-bs-target="#carousel<?=$p['codproduto']?>" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span><span class="visually-hidden">Anterior</span></button><button class="carousel-control-next" type="button" data-bs-target="#carousel<?=$p['codproduto']?>" data-bs-slide="next"><span class="carousel-control-next-icon"></span><span class="visually-hidden">Próxima</span></button><?php endif; ?></div>
              </div></div></div></div>
              <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <div class="col-lg-5">
      <section class="painel mb-4" id="form-produto">
        <span class="eyebrow">CADASTRO</span><h2><?= $editarProduto ? 'Editar produto' : 'Novo produto' ?></h2>
        <form method="post" enctype="multipart/form-data" id="produtoForm" novalidate>
          <input type="hidden" name="acao" value="produto_salvar"><input type="hidden" name="codproduto" value="<?=h($editarProduto['codproduto'] ?? 0)?>">
          <div class="mb-3"><label class="form-label" for="nomeproduto">Nome do produto *</label><input class="form-control" id="nomeproduto" name="nomeproduto" maxlength="150" required value="<?=h($editarProduto['nomeproduto'] ?? '')?>"><div class="invalid-feedback">Informe o nome do produto.</div></div>
          <div class="row"><div class="col-6 mb-3"><label class="form-label" for="quantidade">Quantidade *</label><input class="form-control" id="quantidade" name="quantidade" type="number" min="0" step="0.01" required value="<?=h($editarProduto['quantidade'] ?? '')?>"></div><div class="col-6 mb-3"><label class="form-label" for="valor">Valor (R$) *</label><input class="form-control" id="valor" name="valor" type="number" min="0" step="0.01" required value="<?=h($editarProduto['valor'] ?? '')?>"></div></div>
          <div class="mb-3"><label class="form-label" for="datavencimento">Data de vencimento *</label><input class="form-control" id="datavencimento" name="datavencimento" type="date" min="<?=h($hoje)?>" required value="<?=h($editarProduto['datavencimento'] ?? '')?>"><div class="invalid-feedback">Informe uma data válida, a partir de hoje.</div></div>
          <div class="mb-3"><label class="form-label" for="codtipofk">Categoria *</label><select class="form-select" id="codtipofk" name="codtipofk" required><option value="">Selecione...</option><?php foreach($tipos as $t): ?><option value="<?=$t['codtipo']?>" <?=((int)($editarProduto['codtipofk'] ?? 0)===(int)$t['codtipo'])?'selected':''?>><?=h($t['nometipo'])?></option><?php endforeach; ?></select></div>
          <div class="mb-3"><label class="form-label" for="fotos">Fotos do produto</label><input class="form-control" id="fotos" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple><div class="form-text">Você pode selecionar várias imagens (JPG, PNG, WEBP ou GIF; até 5 MB cada). Ao editar, as novas fotos serão adicionadas às atuais.</div><div id="preview" class="preview-grid mt-2"></div></div>
          <button class="btn btn-success w-100" type="submit"><?= $editarProduto ? 'Salvar alterações' : 'Cadastrar produto' ?></button>
          <?php if($editarProduto): ?><a href="index.php#form-produto" class="btn btn-link w-100 mt-1">Cancelar edição</a><?php endif; ?>
        </form>
      </section>

      <section class="painel">
        <span class="eyebrow">CATEGORIAS</span><h2>Tipos de produto</h2>
        <form method="post" class="d-flex gap-2 mb-3"><input type="hidden" name="acao" value="tipo_salvar"><input type="hidden" name="codtipo" value="<?=h($editarTipo['codtipo'] ?? 0)?>"><input class="form-control" name="nometipo" maxlength="100" required placeholder="Ex.: Frutas" value="<?=h($editarTipo['nometipo'] ?? '')?>"><button class="btn btn-outline-success"><?= $editarTipo ? 'Salvar' : 'Adicionar' ?></button></form>
        <?php foreach($tipos as $t): ?><div class="category-row"><span><?=h($t['nometipo'])?></span><div><a class="btn btn-sm btn-light" href="?editar_tipo=<?=$t['codtipo']?>">Editar</a><form method="post" class="d-inline" onsubmit="return confirm('Excluir esta categoria? Ela não pode estar vinculada a produtos.')"><input type="hidden" name="acao" value="tipo_excluir"><input type="hidden" name="codtipo" value="<?=$t['codtipo']?>"><button class="btn btn-sm btn-outline-danger">Excluir</button></form></div></div><?php endforeach; ?>
      </section>
    </div>
  </div>
  <footer class="text-center text-secondary small py-4">FeiraFácil • Sistema acadêmico — Desenvolvimento Web I</footer>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body></html>
